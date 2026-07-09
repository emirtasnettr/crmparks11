<?php

namespace App\Modules\Courier\Services;

use App\Models\EarningLine;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Support\EarningStatusMapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CourierEarningService
{
    public function __construct(
        private readonly CourierEarningPresenter $presenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, EarningLine>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with(['business', 'courier.agency', 'status'])
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, EarningLine>
     */
    public function forCourier(int $courierId): Collection
    {
        return EarningLine::query()
            ->where('courier_id', $courierId)
            ->with(['business', 'courier.agency', 'status'])
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();
    }

    public function find(int $id): ?EarningLine
    {
        return EarningLine::query()
            ->with(['business', 'courier.agency', 'status', 'creator'])
            ->find($id);
    }

    /**
     * @param  Collection<int, EarningLine>  $items
     * @return array<string, float|int>
     */
    public function summarize(Collection $items): array
    {
        $rows = $items->map(fn (EarningLine $line) => $this->presenter->indexRow($line));
        $active = $rows->where('payment_status', '!=', 'cancelled');
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        return [
            'count' => $rows->count(),
            'total_payable' => round($active->sum('net_payment'), 2),
            'paid_amount' => round($active->whereIn('payment_status', ['paid', 'partial'])->sum('paid_amount'), 2),
            'pending_count' => $active->where('payment_status', 'pending')->count(),
            'this_month_count' => $rows
                ->where('period_month', $currentMonth)
                ->where('period_year', $currentYear)
                ->count(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function couriers(): array
    {
        return Courier::query()
            ->orderBy('full_name')
            ->get(['id', 'full_name'])
            ->map(fn (Courier $courier) => [
                'id' => $courier->id,
                'name' => $courier->full_name,
            ])
            ->all();
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
     * @return array<int, array{id: int, name: string}>
     */
    public function agencies(): array
    {
        return Agency::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (Agency $agency) => [
                'id' => $agency->id,
                'name' => $agency->company_name,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return EarningLine::query()
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = mb_strtolower((string) $filters['search']);

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereHas('courier', fn (Builder $courier) => $courier->whereRaw('LOWER(full_name) LIKE ?', ['%'.$search.'%']))
                        ->orWhereHas('business', fn (Builder $business) => $business->whereRaw('LOWER(company_name) LIKE ?', ['%'.$search.'%']));
                });
            })
            ->when(! empty($filters['courier_id']) && $filters['courier_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('courier_id', (int) $filters['courier_id']);
            })
            ->when(! empty($filters['business_id']) && $filters['business_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('business_id', (int) $filters['business_id']);
            })
            ->when(! empty($filters['agency_id']) && $filters['agency_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('courier', fn (Builder $inner) => $inner->where('agency_id', (int) $filters['agency_id']));
            })
            ->when(! empty($filters['period_month']) && $filters['period_month'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('period_month', (int) $filters['period_month']);
            })
            ->when(! empty($filters['period_year']) && $filters['period_year'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('period_year', (int) $filters['period_year']);
            })
            ->when(! empty($filters['courier_type']) && $filters['courier_type'] !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('courier', fn (Builder $inner) => $inner->where('courier_type', $filters['courier_type']));
            })
            ->when(! empty($filters['payment_status']) && $filters['payment_status'] !== 'all', function (Builder $query) use ($filters): void {
                if ($filters['payment_status'] === 'paid') {
                    $query->whereNotNull('paid_at');
                } elseif ($filters['payment_status'] === 'pending') {
                    $query->whereNull('paid_at')
                        ->whereHas('status', fn (Builder $inner) => $inner->whereNot('code', 'cancelled'));
                } elseif ($filters['payment_status'] === 'cancelled') {
                    $query->whereHas('status', fn (Builder $inner) => $inner->where('code', 'cancelled'));
                }
            });
    }
}
