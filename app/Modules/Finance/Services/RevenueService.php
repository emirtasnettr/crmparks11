<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Business\Data\BusinessEarningFormData;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceRevenue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RevenueService
{
    public function __construct(
        private readonly RevenuePresenter $presenter,
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
            ->with(['business.city', 'business.district', 'currentAccount', 'earningLine'])
            ->orderByDesc('revenue_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FinanceRevenue $revenue) => $this->presenter->indexRow($revenue));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function summarize(array $filters): array
    {
        $items = $this->filter($filters);
        $today = Carbon::today();

        $thisMonth = $items->filter(
            fn (array $row) => Carbon::parse($row['revenue_date'])->isSameMonth($today)
        );

        $collected = $items->where('collection_status', 'collected');
        $pending = $items->whereIn('collection_status', ['pending', 'overdue']);
        $businessCount = $items->pluck('business_id')->unique()->count();

        return [
            'total_revenue' => round($items->sum('amount'), 2),
            'this_month_revenue' => round($thisMonth->sum('amount'), 2),
            'collected_amount' => round($collected->sum('amount'), 2),
            'pending_collection' => round($pending->sum('amount'), 2),
            'average_per_business' => $businessCount > 0
                ? round($items->sum('amount') / $businessCount, 2)
                : 0,
        ];
    }

    public function find(int $id): ?FinanceRevenue
    {
        return FinanceRevenue::query()
            ->with(['business.city', 'business.district', 'currentAccount', 'earningLine'])
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
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): FinanceRevenue
    {
        return DB::transaction(function () use ($data, $user): FinanceRevenue {
            $business = Business::query()->findOrFail((int) $data['business_id']);
            $account = $this->currentAccounts->ensureForEntity($business);
            [$periodMonth, $periodYear] = $this->parsePeriodLabel($data['period_label'] ?? null);
            $invoiceNo = trim((string) ($data['invoice_no'] ?? '')) ?: null;
            $collectionStatus = $data['collection_status'] ?? 'pending';
            $revenueDate = Carbon::parse($data['revenue_date'] ?? now()->toDateString());

            $revenue = FinanceRevenue::query()->create([
                'business_id' => $business->id,
                'earning_line_id' => $data['earning_line_id'] ?? null,
                'current_account_id' => $account->id,
                'revenue_type' => $data['revenue_type'],
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'period_label' => $data['period_label'] ?? null,
                'invoice_no' => $invoiceNo,
                'invoice_status' => $invoiceNo ? 'issued' : 'none',
                'amount' => round((float) $data['amount'], 2),
                'vat_rate' => (int) ($data['vat_rate'] ?? 20),
                'collection_status' => $collectionStatus,
                'collection_date' => $collectionStatus === 'collected'
                    ? ($data['collection_date'] ?? $revenueDate->toDateString())
                    : null,
                'revenue_date' => $revenueDate->toDateString(),
                'description' => $data['description'] ?? $this->defaultDescription($data['revenue_type'], $business->brand_name ?? $business->company_name),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $revenue->update([
                'reference' => sprintf('GLR-%d-%06d', $revenueDate->year, $revenue->id),
            ]);

            $this->recordCurrentAccountMovement($revenue->fresh(), $user);

            $this->activityLog->log(
                'revenue_created',
                $revenue,
                description: "{$revenue->reference} gelir kaydı oluşturuldu.",
            );

            return $revenue->fresh(['business.city', 'business.district', 'currentAccount', 'earningLine']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $user): FinanceRevenue
    {
        return DB::transaction(function () use ($id, $data, $user): FinanceRevenue {
            $revenue = $this->find($id);

            if ($revenue === null) {
                abort(404);
            }

            if (! $this->canUpdate($revenue)) {
                throw ValidationException::withMessages([
                    'revenue' => 'Bu gelir kaydı güncellenemez.',
                ]);
            }

            $business = Business::query()->findOrFail((int) $data['business_id']);
            $account = $this->currentAccounts->ensureForEntity($business);
            [$periodMonth, $periodYear] = $this->parsePeriodLabel($data['period_label'] ?? null);
            $invoiceNo = trim((string) ($data['invoice_no'] ?? '')) ?: null;
            $collectionStatus = $data['collection_status'] ?? $revenue->collection_status;
            $revenueDate = Carbon::parse($data['revenue_date'] ?? $revenue->revenue_date->toDateString());

            $oldValues = $revenue->only([
                'business_id', 'revenue_type', 'period_label', 'invoice_no', 'amount',
                'vat_rate', 'collection_status', 'revenue_date', 'description', 'notes',
            ]);

            $revenue->update([
                'business_id' => $business->id,
                'current_account_id' => $account->id,
                'revenue_type' => $data['revenue_type'],
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'period_label' => $data['period_label'] ?? null,
                'invoice_no' => $invoiceNo,
                'invoice_status' => $invoiceNo ? 'issued' : 'none',
                'amount' => round((float) $data['amount'], 2),
                'vat_rate' => (int) ($data['vat_rate'] ?? 20),
                'collection_status' => $collectionStatus,
                'collection_date' => $collectionStatus === 'collected'
                    ? ($data['collection_date'] ?? $revenue->collection_date?->toDateString() ?? $revenueDate->toDateString())
                    : null,
                'revenue_date' => $revenueDate->toDateString(),
                'description' => $data['description'] ?? $revenue->description,
                'notes' => $data['notes'] ?? $revenue->notes,
            ]);

            $this->activityLog->log(
                'revenue_updated',
                $revenue,
                description: "{$revenue->reference} gelir kaydı güncellendi.",
                oldValues: $oldValues,
                newValues: $revenue->fresh()->only(array_keys($oldValues)),
            );

            return $revenue->fresh(['business.city', 'business.district', 'currentAccount', 'earningLine']);
        });
    }

    public function canUpdate(FinanceRevenue $revenue): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $reference = Carbon::today();

        return FinanceRevenue::query()
            ->when(
                ($filters['business_id'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('business_id', (int) $filters['business_id'])
            )
            ->when(
                ($filters['revenue_type'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('revenue_type', $filters['revenue_type'])
            )
            ->when(
                ($filters['collection_status'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('collection_status', $filters['collection_status'])
            )
            ->when(
                ($filters['invoice_status'] ?? 'all') !== 'all',
                fn (Builder $query) => $query->where('invoice_status', $filters['invoice_status'])
            )
            ->when(($filters['date_range'] ?? 'all') !== 'all', function (Builder $query) use ($filters, $reference): void {
                $range = $filters['date_range'];

                if ($range === 'today') {
                    $query->whereDate('revenue_date', $reference);

                    return;
                }

                if ($range === 'week') {
                    $query->whereBetween('revenue_date', [
                        $reference->copy()->startOfWeek()->toDateString(),
                        $reference->copy()->endOfWeek()->toDateString(),
                    ]);

                    return;
                }

                if ($range === 'month') {
                    $query->whereYear('revenue_date', $reference->year)
                        ->whereMonth('revenue_date', $reference->month);

                    return;
                }

                if ($range === 'year') {
                    $query->whereYear('revenue_date', $reference->year);
                }
            });
    }

    private function recordCurrentAccountMovement(FinanceRevenue $revenue, User $user): void
    {
        if ($revenue->current_account_id === null) {
            return;
        }

        $movementType = $revenue->collection_status === 'collected' ? 'collection' : 'invoice';

        $this->currentAccounts->createMovement([
            'current_account_id' => $revenue->current_account_id,
            'transaction_date' => $revenue->revenue_date->toDateString(),
            'type' => $movementType,
            'document_no' => $revenue->reference,
            'amount' => (float) $revenue->amount,
            'description' => 'Gelir kaydı: '.$revenue->reference,
        ], $user);
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function parsePeriodLabel(?string $label): array
    {
        if ($label === null || trim($label) === '') {
            return [null, null];
        }

        $months = BusinessEarningFormData::months();
        $normalized = mb_strtolower(trim($label));

        foreach ($months as $number => $name) {
            if (str_contains($normalized, mb_strtolower($name))) {
                if (preg_match('/(20\d{2})/', $label, $matches) === 1) {
                    return [$number, (int) $matches[1]];
                }

                return [$number, (int) now()->format('Y')];
            }
        }

        return [null, null];
    }

    private function defaultDescription(string $type, string $brand): string
    {
        return match ($type) {
            'per_package' => $brand.' paket başı hizmet geliri',
            'fixed_monthly' => $brand.' aylık sabit hizmet bedeli',
            'extra_service' => $brand.' ek operasyon hizmeti',
            'penalty' => $brand.' ceza bedeli tahakkuku',
            'manual' => 'Manuel gelir kaydı — '.$brand,
            default => $brand.' diğer gelir kalemi',
        };
    }
}
