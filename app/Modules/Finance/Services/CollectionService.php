<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceCollectionPayment;
use App\Modules\Finance\Models\FinanceRevenue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->company_name,
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

        if ($collection->current_account_id !== null) {
            $this->currentAccounts->createMovement([
                'current_account_id' => $collection->current_account_id,
                'transaction_date' => $payment->payment_date->toDateString(),
                'type' => 'collection',
                'document_no' => $payment->payment_reference ?? $collection->reference,
                'amount' => (float) $payment->amount,
                'description' => 'Tahsilat: '.$collection->reference,
            ], $user);
        }

        return $payment;
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
