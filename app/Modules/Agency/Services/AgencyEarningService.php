<?php

namespace App\Modules\Agency\Services;

use App\Models\EarningLine;
use App\Modules\Agency\Models\Agency;
use Illuminate\Support\Collection;

class AgencyEarningService
{
    public function __construct(
        private readonly AgencyEarningPresenter $presenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function filter(array $filters): Collection
    {
        return $this->aggregate($this->lines($filters), $filters);
    }

    public function find(int $id): ?array
    {
        $line = EarningLine::query()
            ->with(['business', 'courier.agency.city', 'status'])
            ->find($id);

        if ($line === null || $line->courier?->agency_id === null) {
            return null;
        }

        $agency = $line->courier->agency;
        $lines = EarningLine::query()
            ->where('period_month', $line->period_month)
            ->where('period_year', $line->period_year)
            ->whereHas('courier', fn ($query) => $query->where('agency_id', $agency->id))
            ->with(['business', 'courier', 'status'])
            ->get();

        return $this->presenter->aggregateRow($agency, (int) $line->period_month, (int) $line->period_year, $lines);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function summarize(array $filters): array
    {
        $items = $this->filter($filters);
        $active = $items->where('payment_status', '!=', 'cancelled');
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        return [
            'count' => $items->count(),
            'total_payable' => round($active->sum('net_payment'), 2),
            'paid_amount' => round($active->whereIn('payment_status', ['paid', 'partial'])->sum('paid_amount'), 2),
            'pending_count' => $active->where('payment_status', 'pending')->count(),
            'this_month_count' => $items
                ->where('period_month', $currentMonth)
                ->where('period_year', $currentYear)
                ->count(),
        ];
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
     * @return Collection<int, EarningLine>
     */
    private function lines(array $filters): Collection
    {
        return EarningLine::query()
            ->with(['business', 'courier.agency.city', 'status'])
            ->whereHas('courier', fn ($query) => $query->whereNotNull('agency_id'))
            ->when(! empty($filters['agency_id']) && $filters['agency_id'] !== 'all', function ($query) use ($filters): void {
                $query->whereHas('courier', fn ($inner) => $inner->where('agency_id', (int) $filters['agency_id']));
            })
            ->when(! empty($filters['period_month']) && $filters['period_month'] !== 'all', function ($query) use ($filters): void {
                $query->where('period_month', (int) $filters['period_month']);
            })
            ->when(! empty($filters['period_year']) && $filters['period_year'] !== 'all', function ($query) use ($filters): void {
                $query->where('period_year', (int) $filters['period_year']);
            })
            ->get();
    }

    /**
     * @param  Collection<int, EarningLine>  $lines
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function aggregate(Collection $lines, array $filters): Collection
    {
        $grouped = $lines
            ->groupBy(fn (EarningLine $line) => $line->courier?->agency_id.'-'.$line->period_year.'-'.$line->period_month)
            ->map(function (Collection $group) {
                /** @var EarningLine $first */
                $first = $group->first();
                $agency = $first->courier?->agency;

                if ($agency === null) {
                    return null;
                }

                return $this->presenter->aggregateRow(
                    $agency,
                    (int) $first->period_month,
                    (int) $first->period_year,
                    $group,
                );
            })
            ->filter()
            ->values();

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $grouped = $grouped->where('status', $filters['status'])->values();
        }

        if (! empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $grouped = $grouped->where('payment_status', $filters['payment_status'])->values();
        }

        return $grouped->sortByDesc(fn (array $row) => sprintf('%04d-%02d', $row['period_year'], $row['period_month']))->values();
    }
}
