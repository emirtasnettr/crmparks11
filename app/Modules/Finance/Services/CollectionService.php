<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceCollectionPayment;
use App\Modules\Finance\Models\FinanceInvoice;
use App\Modules\Finance\Models\FinanceRevenue;
use App\Support\SecureUploadValidator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CollectionService
{
    public function __construct(
        private readonly CollectionPresenter $presenter,
        private readonly CurrentAccountService $currentAccounts,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with(['business.city', 'revenue', 'currentAccount', 'payments'])
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FinanceCollection $collection) => $this->presenter->indexRow($collection));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function summarize(array $filters): array
    {
        $items = $this->filter($filters);
        $today = Carbon::today();

        $todayCollected = $items->filter(
            fn (array $row) => $row['collection_date'] && Carbon::parse($row['collection_date'])->isSameDay($today)
        )->sum('collected_amount');

        $monthCollected = $items->filter(
            fn (array $row) => $row['collection_date']
                && Carbon::parse($row['collection_date'])->isSameMonth($today)
        )->sum('collected_amount');

        return [
            'total_amount' => round($items->sum('total_amount'), 2),
            'collected_amount' => round($items->sum('collected_amount'), 2),
            'pending_amount' => round($items->whereIn('status', ['pending', 'partial'])->sum('remaining_amount'), 2),
            'overdue_amount' => round($items->where('status', 'overdue')->sum('remaining_amount'), 2),
            'today_collected' => round($todayCollected, 2),
            'month_collected' => round($monthCollected, 2),
        ];
    }

    public function find(int $id): ?FinanceCollection
    {
        return FinanceCollection::query()
            ->with(['business.city', 'revenue', 'currentAccount', 'payments'])
            ->find($id);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function businesses(): array
    {
        return Business::query()
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->displayName(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, reference: string, business_id: int, invoice_no: ?string}>
     */
    public function revenueOptions(): array
    {
        return FinanceRevenue::query()
            ->orderByDesc('id')
            ->limit(30)
            ->get(['id', 'reference', 'business_id', 'invoice_no'])
            ->map(fn (FinanceRevenue $revenue) => [
                'id' => $revenue->id,
                'reference' => $revenue->reference,
                'business_id' => $revenue->business_id,
                'invoice_no' => $revenue->invoice_no,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): FinanceCollection
    {
        return DB::transaction(function () use ($data, $user): FinanceCollection {
            $business = Business::query()->findOrFail((int) $data['business_id']);
            $account = $this->currentAccounts->ensureForEntity($business);
            $revenueId = ! empty($data['revenue_id']) ? (int) $data['revenue_id'] : null;
            $revenue = $revenueId ? FinanceRevenue::query()->findOrFail($revenueId) : null;
            $dueDate = Carbon::parse($data['due_date']);
            $totalAmount = round((float) $data['total_amount'], 2);
            $collectedAmount = round((float) ($data['collected_amount'] ?? 0), 2);

            if ($collectedAmount > $totalAmount) {
                $collectedAmount = $totalAmount;
            }

            $invoiceNo = trim((string) ($data['invoice_no'] ?? '')) ?: $revenue?->invoice_no;
            $source = $revenueId ? 'revenue' : 'manual';

            $collection = FinanceCollection::query()->create([
                'business_id' => $business->id,
                'revenue_id' => $revenueId,
                'current_account_id' => $account->id,
                'source' => $source,
                'invoice_no' => $invoiceNo,
                'due_date' => $dueDate->toDateString(),
                'total_amount' => $totalAmount,
                'collected_amount' => 0,
                'status' => 'pending',
                'description' => $data['description'] ?? 'İşletme tahsilat kaydı — '.($business->brand_name ?? $business->company_name),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $collection->update([
                'reference' => sprintf('TAH-%d-%06d', $dueDate->year, $collection->id),
            ]);

            if ($collectedAmount > 0) {
                $this->addPayment($collection->fresh(), [
                    'amount' => $collectedAmount,
                    'payment_date' => $data['collection_date'] ?? now()->toDateString(),
                    'payment_method' => $data['payment_method'] ?? null,
                    'payment_reference' => $data['payment_reference'] ?? null,
                    'bank' => $data['bank'] ?? null,
                ], $user);
            } else {
                $this->syncStatus($collection->fresh());
            }

            $this->activityLog->log(
                'collection_created',
                $collection,
                description: "{$collection->reference} tahsilat kaydı oluşturuldu.",
            );

            return $collection->fresh(['business.city', 'revenue', 'currentAccount', 'payments']);
        });
    }

    /**
     * Cari üzerinden alınan tahsilatı açık FinanceCollection kayıtlarına FIFO uygular.
     *
     * @param  array{payment_date: string, payment_method?: ?string, payment_reference?: ?string, document_no?: ?string}  $meta
     * @return array{applied: float, collection_ids: list<int>}
     */
    public function applyAmountToAccount(int $currentAccountId, float $amount, array $meta, User $user): array
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Tahsilat tutarı 0’dan büyük olmalıdır.',
            ]);
        }

        return DB::transaction(function () use ($currentAccountId, $amount, $meta, $user): array {
            $open = FinanceCollection::query()
                ->where('current_account_id', $currentAccountId)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderBy('due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $remainingOpen = round($open->sum(
                fn (FinanceCollection $c) => max(0, (float) $c->total_amount - (float) $c->collected_amount)
            ), 2);

            if ($remainingOpen <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Bu cari için açık tahsilat kaydı yok. Önce Tahsilat/Fatura modülünden alacak oluşturun veya dekont kullanın.',
                ]);
            }

            if ($amount - $remainingOpen > 0.009) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Tutar açık alacakları aşıyor (açık: %s ₺). Fazla tahsilat için tutarı düşürün.',
                        number_format($remainingOpen, 2, ',', '.')
                    ),
                ]);
            }

            $left = $amount;
            $appliedIds = [];
            $paymentDate = $meta['payment_date'] ?? now()->toDateString();

            foreach ($open as $collection) {
                if ($left <= 0) {
                    break;
                }

                $due = round((float) $collection->total_amount - (float) $collection->collected_amount, 2);
                if ($due <= 0) {
                    continue;
                }

                $chunk = min($due, $left);
                $this->addPayment($collection->fresh(['payments']), [
                    'amount' => $chunk,
                    'payment_date' => $paymentDate,
                    'payment_method' => $meta['payment_method'] ?? 'bank_transfer',
                    'payment_reference' => $meta['payment_reference'] ?? $meta['document_no'] ?? null,
                    'bank' => $meta['bank'] ?? null,
                ], $user);

                $appliedIds[] = (int) $collection->id;
                $left = round($left - $chunk, 2);
            }

            return [
                'applied' => round($amount - $left, 2),
                'collection_ids' => $appliedIds,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $user): FinanceCollection
    {
        return DB::transaction(function () use ($id, $data, $user): FinanceCollection {
            $collection = $this->find($id);

            if ($collection === null) {
                abort(404);
            }

            if (! $this->canUpdate($collection)) {
                throw ValidationException::withMessages([
                    'collection' => 'Bu tahsilat kaydı güncellenemez.',
                ]);
            }

            $business = Business::query()->findOrFail((int) $data['business_id']);
            $account = $this->currentAccounts->ensureForEntity($business);
            $revenueId = ! empty($data['revenue_id']) ? (int) $data['revenue_id'] : null;
            $revenue = $revenueId ? FinanceRevenue::query()->findOrFail($revenueId) : null;
            $dueDate = Carbon::parse($data['due_date']);
            $totalAmount = round((float) $data['total_amount'], 2);
            $collectedAmount = round((float) $collection->collected_amount, 2);

            if ($totalAmount < $collectedAmount) {
                throw ValidationException::withMessages([
                    'total_amount' => 'Toplam tutar tahsil edilen tutardan küçük olamaz.',
                ]);
            }

            $invoiceNo = trim((string) ($data['invoice_no'] ?? '')) ?: $revenue?->invoice_no;

            $oldValues = $collection->only([
                'business_id', 'revenue_id', 'invoice_no', 'due_date',
                'total_amount', 'description', 'notes',
            ]);

            $collection->update([
                'business_id' => $business->id,
                'revenue_id' => $revenueId,
                'current_account_id' => $account->id,
                'invoice_no' => $invoiceNo,
                'due_date' => $dueDate->toDateString(),
                'total_amount' => $totalAmount,
                'description' => $data['description'] ?? $collection->description,
                'notes' => $data['notes'] ?? $collection->notes,
            ]);

            $this->syncStatus($collection->fresh(['payments']));

            $this->activityLog->log(
                'collection_updated',
                $collection,
                description: "{$collection->reference} tahsilat kaydı güncellendi.",
                oldValues: $oldValues,
                newValues: $collection->fresh()->only(array_keys($oldValues)),
            );

            return $collection->fresh(['business.city', 'revenue', 'currentAccount', 'payments']);
        });
    }

    public function canUpdate(FinanceCollection $collection): bool
    {
        return $collection->status !== 'collected';
    }

    public function storeReceipt(int $id, UploadedFile $file, User $user): FinanceCollection
    {
        return DB::transaction(function () use ($id, $file, $user): FinanceCollection {
            $collection = $this->find($id);

            if ($collection === null) {
                abort(404);
            }

            $profile = SecureUploadValidator::formDocumentProfile();
            $extension = SecureUploadValidator::assertAllowed(
                $file,
                $profile['extensions'],
                $profile['mimeTypes'],
            );

            $disk = 'public';
            $filename = 'dekont-'.$collection->id.'-'.Str::random(8).'.'.$extension;
            $path = $file->storeAs('finance-receipts/'.$collection->id, $filename, $disk);

            if ($collection->receipt_path) {
                Storage::disk($disk)->delete($collection->receipt_path);
            }

            $collection->update([
                'receipt_path' => $path,
                'receipt_original_name' => $file->getClientOriginalName(),
                'receipt_uploaded_at' => now(),
            ]);

            $this->activityLog->log(
                'collection_receipt_uploaded',
                $collection,
                description: "{$collection->reference} tahsilatına dekont yüklendi.",
            );

            return $collection->fresh(['business.city', 'revenue', 'currentAccount', 'payments']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function collectRemaining(int $id, array $data, User $user): FinanceCollection
    {
        return DB::transaction(function () use ($id, $data, $user): FinanceCollection {
            $collection = $this->find($id);

            if ($collection === null) {
                abort(404);
            }

            if (! $this->canUpdate($collection)) {
                throw ValidationException::withMessages([
                    'ids' => "{$collection->reference} tahsilatı zaten tamamlanmış.",
                ]);
            }

            $remaining = round((float) $collection->total_amount - (float) $collection->collected_amount, 2);

            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    'ids' => "{$collection->reference} için tahsil edilecek tutar yok.",
                ]);
            }

            $this->addPayment($collection, [
                'amount' => $remaining,
                'payment_date' => $data['collection_date'],
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'bank' => $data['bank'] ?? null,
            ], $user);

            $this->activityLog->log(
                'collection_bulk_collected',
                $collection,
                description: "{$collection->reference} toplu tahsilat ile kapatıldı.",
            );

            return $collection->fresh(['business.city', 'revenue', 'currentAccount', 'payments']);
        });
    }

    /**
     * @param  array<int, int|string>  $ids
     * @param  array<string, mixed>  $data
     * @return array{processed: int, failed: int, errors: array<int, string>}
     */
    public function bulkCollect(array $ids, array $data, User $user): array
    {
        $processed = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $this->collectRemaining((int) $id, $data, $user);
                $processed++;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($processed === 0 && $errors !== []) {
            throw ValidationException::withMessages([
                'ids' => 'Hiçbir tahsilat işlenemedi. '.$errors[0],
            ]);
        }

        return [
            'processed' => $processed,
            'failed' => count($errors),
            'errors' => array_slice($errors, 0, 10),
        ];
    }

    /**
     * @param  array<string, mixed>  $paymentData
     */
    private function addPayment(FinanceCollection $collection, array $paymentData, User $user): FinanceCollectionPayment
    {
        $payment = FinanceCollectionPayment::query()->create([
            'collection_id' => $collection->id,
            'amount' => round((float) $paymentData['amount'], 2),
            'payment_date' => Carbon::parse($paymentData['payment_date'])->toDateString(),
            'payment_method' => $paymentData['payment_method'] ?? null,
            'payment_reference' => $paymentData['payment_reference'] ?? null,
            'bank' => $paymentData['bank'] ?? null,
            'created_by' => $user->id,
        ]);

        $this->syncStatus($collection->fresh(['payments']));
        $this->syncLinkedRevenueStatus($collection->fresh());

        if ($collection->current_account_id !== null) {
            $this->currentAccounts->createMovement([
                'current_account_id' => $collection->current_account_id,
                'transaction_date' => $payment->payment_date->toDateString(),
                'type' => 'collection',
                'document_no' => $payment->payment_reference ?? $collection->reference,
                'amount' => (float) $payment->amount,
                'description' => 'Tahsilat: '.$collection->reference,
                'related_type' => FinanceCollectionPayment::class,
                'related_id' => $payment->id,
            ], $user);
        }

        return $payment;
    }

    private function syncLinkedRevenueStatus(FinanceCollection $collection): void
    {
        $revenue = null;

        if ($collection->revenue_id !== null) {
            $revenue = FinanceRevenue::query()->find($collection->revenue_id);
        }

        if ($revenue === null && filled($collection->invoice_no)) {
            $invoice = FinanceInvoice::query()
                ->where('reference', $collection->invoice_no)
                ->first();

            if ($invoice?->earning_line_id) {
                $revenue = FinanceRevenue::query()
                    ->where('earning_line_id', $invoice->earning_line_id)
                    ->first();
            }
        }

        if ($revenue === null) {
            return;
        }

        $status = $collection->status;
        $mapped = match ($status) {
            'collected' => 'collected',
            'partial' => 'partial',
            default => 'pending',
        };

        $revenue->update([
            'collection_status' => $mapped,
            'collection_date' => $mapped === 'collected' ? now()->toDateString() : $revenue->collection_date,
        ]);
    }

    private function syncStatus(FinanceCollection $collection): void
    {
        $collected = round((float) $collection->payments->sum('amount'), 2);
        $total = (float) $collection->total_amount;

        $collection->update([
            'collected_amount' => $collected,
            'status' => $this->resolveStatus($total, $collected, $collection->due_date),
        ]);
    }

    private function resolveStatus(float $total, float $collected, Carbon $dueDate): string
    {
        $remaining = round($total - $collected, 2);

        if ($remaining <= 0) {
            return 'collected';
        }

        if ($collected > 0) {
            return 'partial';
        }

        if ($dueDate->lt(Carbon::today())) {
            return 'overdue';
        }

        return 'pending';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $reference = Carbon::today();

        return FinanceCollection::query()
            ->when(
                ($filters['business_id'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('business_id', (int) $filters['business_id'])
            )
            ->when(
                ($filters['collection_status'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('status', $filters['collection_status'])
            )
            ->when(
                ($filters['payment_method'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->whereHas(
                    'payments',
                    fn (Builder $paymentQuery) => $paymentQuery->where('payment_method', $filters['payment_method'])
                )
            )
            ->when(($filters['date_range'] ?? 'all') !== 'all', function (Builder $query) use ($filters, $reference): void {
                $range = $filters['date_range'];

                $query->whereHas('payments', function (Builder $paymentQuery) use ($range, $reference): void {
                    if ($range === 'today') {
                        $paymentQuery->whereDate('payment_date', $reference);

                        return;
                    }

                    if ($range === 'week') {
                        $paymentQuery->whereBetween('payment_date', [
                            $reference->copy()->startOfWeek()->toDateString(),
                            $reference->copy()->endOfWeek()->toDateString(),
                        ]);

                        return;
                    }

                    if ($range === 'month') {
                        $paymentQuery->whereYear('payment_date', $reference->year)
                            ->whereMonth('payment_date', $reference->month);

                        return;
                    }

                    if ($range === 'year') {
                        $paymentQuery->whereYear('payment_date', $reference->year);
                    }
                });
            })
            ->when(($filters['due_date'] ?? 'all') !== 'all', function (Builder $query) use ($filters, $reference): void {
                $dueFilter = $filters['due_date'];

                if ($dueFilter === 'overdue') {
                    $query->where('status', 'overdue');

                    return;
                }

                if ($dueFilter === 'today') {
                    $query->whereDate('due_date', $reference);

                    return;
                }

                if ($dueFilter === 'week') {
                    $query->whereBetween('due_date', [
                        $reference->copy()->startOfWeek()->toDateString(),
                        $reference->copy()->endOfWeek()->toDateString(),
                    ]);

                    return;
                }

                if ($dueFilter === 'month') {
                    $query->whereYear('due_date', $reference->year)
                        ->whereMonth('due_date', $reference->month);
                }
            });
    }
}
