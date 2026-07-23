<?php

namespace App\Modules\Finance\Services;

use App\Models\EarningLine;
use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceInvoice;
use App\Modules\Finance\Models\FinanceRevenue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private readonly InvoicePresenter $presenter,
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
            ->with(['business.city', 'business.district', 'earningLine', 'currentAccount', 'collection'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FinanceInvoice $invoice) => $this->presenter->indexRow($invoice));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function summarize(array $filters): array
    {
        $items = $this->filter($filters);
        $today = Carbon::today();

        $active = $items->where('invoice_status', '!=', 'cancelled');

        $thisMonth = $active->filter(
            fn (array $row) => Carbon::parse($row['invoice_date'])->isSameMonth($today)
                && $row['invoice_status'] === 'issued'
        );

        return [
            'total_invoice' => round($active->sum('subtotal'), 2),
            'this_month_issued' => round($thisMonth->sum('subtotal'), 2),
            'collected_amount' => round($active->sum('collected_amount'), 2),
            'pending_amount' => round($active->whereIn('collection_status', ['pending', 'partial', 'overdue'])->sum('remaining_amount'), 2),
            'cancelled_count' => $items->where('invoice_status', 'cancelled')->count(),
        ];
    }

    public function find(int $id): ?FinanceInvoice
    {
        return FinanceInvoice::query()
            ->with(['business.city', 'business.district', 'earningLine', 'currentAccount', 'collection'])
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
     * @return array<int, array{id: int, reference: string, business_id: int, period_label: string, amount: float}>
     */
    public function earningOptions(): array
    {
        $usedEarningIds = FinanceInvoice::query()
            ->whereNotNull('earning_line_id')
            ->pluck('earning_line_id')
            ->all();

        $months = \App\Modules\Business\Data\BusinessEarningFormData::months();

        return EarningLine::query()
            ->whereNotIn('id', $usedEarningIds)
            ->whereHas('status', fn (Builder $query) => $query->whereIn('code', ['approved', 'paid', 'pending_review']))
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->limit(50)
            ->get(['id', 'business_id', 'period_month', 'period_year', 'revenue_total'])
            ->map(fn (EarningLine $line) => [
                'id' => $line->id,
                'reference' => sprintf('ISH-%06d', $line->id),
                'business_id' => $line->business_id,
                'period_label' => ($months[$line->period_month] ?? '').' '.$line->period_year,
                'amount' => (float) $line->revenue_total,
            ])
            ->all();
    }

    /**
     * @param  array<int, int|string>  $earningIds
     * @param  array<string, mixed>  $data
     * @return array{processed: int, failed: int, errors: array<int, string>}
     */
    public function bulkCreateFromEarnings(array $earningIds, array $data, User $user): array
    {
        $processed = 0;
        $errors = [];
        $invoiceDate = Carbon::parse($data['invoice_date']);
        $dueDate = isset($data['due_date'])
            ? Carbon::parse($data['due_date'])
            : $invoiceDate->copy()->addDays(15);

        foreach ($earningIds as $earningId) {
            try {
                $line = EarningLine::query()->with('status')->find((int) $earningId);

                if ($line === null) {
                    $errors[] = "Hakediş #{$earningId} bulunamadı.";

                    continue;
                }

                if (FinanceInvoice::query()->where('earning_line_id', $line->id)->exists()) {
                    $errors[] = sprintf('ISH-%06d için fatura zaten var.', $line->id);

                    continue;
                }

                $this->create([
                    'business_id' => $line->business_id,
                    'earning_line_id' => $line->id,
                    'invoice_type' => $data['invoice_type'] ?? 'manual',
                    'invoice_date' => $invoiceDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'subtotal' => round((float) $line->revenue_total + (float) $line->extra_payment, 2),
                    'vat_rate' => (int) ($data['vat_rate'] ?? 20),
                    'description' => $data['description'] ?? null,
                ], $user);

                $processed++;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($processed === 0 && $errors !== []) {
            throw ValidationException::withMessages([
                'earning_ids' => 'Hiçbir fatura oluşturulamadı. '.$errors[0],
            ]);
        }

        return [
            'processed' => $processed,
            'failed' => count($errors),
            'errors' => array_slice($errors, 0, 10),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): FinanceInvoice
    {
        return DB::transaction(function () use ($data, $user): FinanceInvoice {
            $business = Business::query()->findOrFail((int) $data['business_id']);
            $account = $this->currentAccounts->ensureForEntity($business);
            $earningLineId = ! empty($data['earning_line_id']) ? (int) $data['earning_line_id'] : null;
            $earningLine = $earningLineId ? EarningLine::query()->findOrFail($earningLineId) : null;
            $invoiceType = $data['invoice_type'] ?? 'manual';
            $invoiceDate = Carbon::parse($data['invoice_date']);
            $dueDate = Carbon::parse($data['due_date']);
            $subtotal = round((float) $data['subtotal'], 2);
            $vatRate = (int) ($data['vat_rate'] ?? 20);
            $vatAmount = round($subtotal * ($vatRate / 100), 2);
            $grandTotal = round($subtotal + $vatAmount, 2);
            $invoiceStatus = 'issued';
            $source = $earningLineId ? 'earning' : 'manual';
            $collectionStatus = $this->resolveCollectionStatus(0, $subtotal, $dueDate, $invoiceStatus);

            $invoice = FinanceInvoice::query()->create([
                'business_id' => $business->id,
                'earning_line_id' => $earningLineId,
                'current_account_id' => $account->id,
                'invoice_type' => $invoiceType,
                'invoice_status' => $invoiceStatus,
                'collection_status' => $collectionStatus,
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'subtotal' => $subtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'grand_total' => $grandTotal,
                'collected_amount' => 0,
                'source' => $source,
                'e_invoice_uuid' => $invoiceType === 'e_invoice'
                    ? sprintf('E-FATURA-%s-%06d', $invoiceDate->year, 0)
                    : null,
                'e_archive_uuid' => $invoiceType === 'e_archive'
                    ? sprintf('E-ARSIV-%s-%06d', $invoiceDate->year, 0)
                    : null,
                'gib_status' => in_array($invoiceType, ['e_invoice', 'e_archive'], true) ? 'sent' : 'not_applicable',
                'description' => $data['description'] ?? $this->defaultDescription($earningLine, $source),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $invoice->update([
                'reference' => sprintf('FTR-%d-%06d', $invoiceDate->year, $invoice->id),
                'pdf_filename' => sprintf('FTR-%d-%06d.pdf', $invoiceDate->year, $invoice->id),
                'e_invoice_uuid' => $invoiceType === 'e_invoice'
                    ? sprintf('E-FATURA-%s-%06d', $invoiceDate->year, $invoice->id)
                    : null,
                'e_archive_uuid' => $invoiceType === 'e_archive'
                    ? sprintf('E-ARSIV-%s-%06d', $invoiceDate->year, $invoice->id)
                    : null,
            ]);

            $collection = $this->createCollectionForInvoice($invoice->fresh(), $user);
            $invoice->update(['collection_id' => $collection->id]);

            $this->recordCurrentAccountMovement($invoice->fresh(), $user);

            $this->activityLog->log(
                'invoice_created',
                $invoice,
                description: "{$invoice->reference} fatura kaydı oluşturuldu.",
            );

            return $invoice->fresh(['business.city', 'business.district', 'earningLine', 'currentAccount', 'collection']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $user): FinanceInvoice
    {
        return DB::transaction(function () use ($id, $data, $user): FinanceInvoice {
            $invoice = $this->find($id);

            if ($invoice === null) {
                abort(404);
            }

            if (! $this->canUpdate($invoice)) {
                throw ValidationException::withMessages([
                    'invoice' => 'Bu fatura kaydı güncellenemez.',
                ]);
            }

            $business = Business::query()->findOrFail((int) $data['business_id']);
            $account = $this->currentAccounts->ensureForEntity($business);
            $invoiceDate = Carbon::parse($data['invoice_date']);
            $dueDate = Carbon::parse($data['due_date']);
            $subtotal = round((float) $data['subtotal'], 2);
            $vatRate = (int) ($data['vat_rate'] ?? 20);
            $vatAmount = round($subtotal * ($vatRate / 100), 2);
            $grandTotal = round($subtotal + $vatAmount, 2);
            $collectedAmount = round((float) $invoice->collected_amount, 2);

            if ($subtotal < $collectedAmount) {
                throw ValidationException::withMessages([
                    'subtotal' => 'Ara toplam tahsil edilen tutardan küçük olamaz.',
                ]);
            }

            $oldValues = $invoice->only([
                'business_id', 'invoice_type', 'invoice_date', 'due_date',
                'subtotal', 'vat_rate', 'description', 'notes',
            ]);

            $invoice->update([
                'business_id' => $business->id,
                'current_account_id' => $account->id,
                'invoice_type' => $data['invoice_type'] ?? $invoice->invoice_type,
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'subtotal' => $subtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'grand_total' => $grandTotal,
                'collection_status' => $this->resolveCollectionStatus(
                    $collectedAmount,
                    $subtotal,
                    $dueDate,
                    $invoice->invoice_status,
                ),
                'description' => $data['description'] ?? $invoice->description,
                'notes' => $data['notes'] ?? $invoice->notes,
            ]);

            if ($invoice->collection_id !== null) {
                FinanceCollection::query()
                    ->whereKey($invoice->collection_id)
                    ->update([
                        'due_date' => $dueDate->toDateString(),
                        'total_amount' => $subtotal,
                    ]);
            }

            $this->activityLog->log(
                'invoice_updated',
                $invoice,
                description: "{$invoice->reference} fatura kaydı güncellendi.",
                oldValues: $oldValues,
                newValues: $invoice->fresh()->only(array_keys($oldValues)),
            );

            return $invoice->fresh(['business.city', 'business.district', 'earningLine', 'currentAccount', 'collection']);
        });
    }

    public function canUpdate(FinanceInvoice $invoice): bool
    {
        return $invoice->invoice_status !== 'cancelled';
    }

    public function cancel(int $id, User $user): FinanceInvoice
    {
        return DB::transaction(function () use ($id, $user): FinanceInvoice {
            $invoice = $this->find($id);

            if ($invoice === null) {
                abort(404);
            }

            if (! $this->canUpdate($invoice)) {
                throw ValidationException::withMessages([
                    'invoice' => 'Bu fatura zaten iptal edilmiş.',
                ]);
            }

            if ((float) $invoice->collected_amount > 0) {
                throw ValidationException::withMessages([
                    'invoice' => 'Tahsilatı başlamış faturalar iptal edilemez.',
                ]);
            }

            $oldStatus = $invoice->invoice_status;

            $invoice->update([
                'invoice_status' => 'cancelled',
                'collection_status' => 'pending',
                'gib_status' => in_array($invoice->invoice_type, ['e_invoice', 'e_archive'], true)
                    ? 'cancelled'
                    : $invoice->gib_status,
            ]);

            if ($invoice->collection_id !== null) {
                FinanceCollection::query()
                    ->whereKey($invoice->collection_id)
                    ->where('collected_amount', 0)
                    ->update(['status' => 'pending']);
            }

            if ($oldStatus === 'issued' && $invoice->current_account_id !== null) {
                $this->currentAccounts->createMovement([
                    'current_account_id' => $invoice->current_account_id,
                    'transaction_date' => now()->toDateString(),
                    'type' => 'credit_note',
                    'document_no' => $invoice->reference,
                    'amount' => (float) $invoice->subtotal,
                    'description' => 'Fatura iptali: '.$invoice->reference,
                    'related_type' => FinanceInvoice::class,
                    'related_id' => $invoice->id,
                ], $user);
            }

            $this->activityLog->log(
                'invoice_cancelled',
                $invoice,
                description: "{$invoice->reference} fatura kaydı iptal edildi.",
                oldValues: ['invoice_status' => $oldStatus],
                newValues: ['invoice_status' => 'cancelled'],
            );

            return $invoice->fresh(['business.city', 'business.district', 'earningLine', 'currentAccount', 'collection']);
        });
    }

    private function createCollectionForInvoice(FinanceInvoice $invoice, User $user): FinanceCollection
    {
        $revenueId = $invoice->earning_line_id
            ? FinanceRevenue::query()->where('earning_line_id', $invoice->earning_line_id)->value('id')
            : null;

        $collection = FinanceCollection::query()->create([
            'business_id' => $invoice->business_id,
            'current_account_id' => $invoice->current_account_id,
            'revenue_id' => $revenueId,
            'source' => $invoice->source === 'earning' ? 'revenue' : 'manual',
            'invoice_no' => $invoice->reference,
            'due_date' => $invoice->due_date->toDateString(),
            'total_amount' => $invoice->subtotal,
            'collected_amount' => 0,
            'status' => $invoice->collection_status,
            'description' => 'Fatura tahsilat kaydı — '.$invoice->reference,
            'created_by' => $user->id,
        ]);

        $collection->update([
            'reference' => sprintf('TAH-%d-%06d', $invoice->due_date->year, $collection->id),
        ]);

        return $collection->fresh();
    }

    private function recordCurrentAccountMovement(FinanceInvoice $invoice, User $user): void
    {
        if ($invoice->current_account_id === null || $invoice->invoice_status !== 'issued') {
            return;
        }

        // Hakediş geliri onayda cariye yazıldıysa faturada tekrar borç yazma.
        if ($invoice->earning_line_id !== null) {
            $revenuePosted = FinanceRevenue::query()
                ->where('earning_line_id', $invoice->earning_line_id)
                ->whereNotNull('current_account_id')
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('current_account_movements')
                        ->whereColumn('current_account_movements.related_id', 'finance_revenues.id')
                        ->where('current_account_movements.related_type', FinanceRevenue::class);
                })
                ->exists();

            if ($revenuePosted) {
                return;
            }
        }

        $this->currentAccounts->createMovement([
            'current_account_id' => $invoice->current_account_id,
            'transaction_date' => $invoice->invoice_date->toDateString(),
            'type' => 'invoice',
            'document_no' => $invoice->reference,
            'amount' => (float) $invoice->subtotal,
            'description' => 'Fatura: '.$invoice->reference,
            'related_type' => FinanceInvoice::class,
            'related_id' => $invoice->id,
        ], $user);
    }

    private function resolveCollectionStatus(
        float $collected,
        float $subtotal,
        Carbon $dueDate,
        string $invoiceStatus,
    ): string {
        if (in_array($invoiceStatus, ['draft', 'cancelled'], true)) {
            return 'pending';
        }

        $remaining = round($subtotal - $collected, 2);

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

    private function defaultDescription(?EarningLine $earningLine, string $source): string
    {
        if ($source === 'earning' && $earningLine !== null) {
            $months = \App\Modules\Business\Data\BusinessEarningFormData::months();
            $period = ($months[$earningLine->period_month] ?? '').' '.$earningLine->period_year;

            return 'Hakediş dönemi faturası — '.$period;
        }

        return 'Manuel fatura kaydı';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $reference = Carbon::today();

        return FinanceInvoice::query()
            ->when(
                ($filters['business_id'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('business_id', (int) $filters['business_id'])
            )
            ->when(
                ($filters['invoice_type'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('invoice_type', $filters['invoice_type'])
            )
            ->when(
                ($filters['invoice_status'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('invoice_status', $filters['invoice_status'])
            )
            ->when(
                ($filters['collection_status'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('collection_status', $filters['collection_status'])
            )
            ->when(($filters['date_range'] ?? 'all') !== 'all', function (Builder $query) use ($filters, $reference): void {
                $range = $filters['date_range'];

                if ($range === 'today') {
                    $query->whereDate('invoice_date', $reference);

                    return;
                }

                if ($range === 'week') {
                    $query->whereBetween('invoice_date', [
                        $reference->copy()->startOfWeek()->toDateString(),
                        $reference->copy()->endOfWeek()->toDateString(),
                    ]);

                    return;
                }

                if ($range === 'month') {
                    $query->whereYear('invoice_date', $reference->year)
                        ->whereMonth('invoice_date', $reference->month);

                    return;
                }

                if ($range === 'year') {
                    $query->whereYear('invoice_date', $reference->year);
                }
            });
    }
}
