<?php

namespace App\Modules\Courier\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourierWorkHistoryService
{
    public function __construct(
        private readonly CourierWorkHistoryPresenter $presenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, BusinessCourierAssignment>
     */
    public function filter(array $filters): Collection
    {
        $assignments = $this->baseQuery($filters)
            ->with(['business', 'courier.agency'])
            ->orderByDesc('start_date')
            ->get();

        return $this->applyPresentationFilters($assignments, $filters)
            ->sortByDesc(fn (BusinessCourierAssignment $assignment) => sprintf(
                '%d-%s',
                in_array($this->presenter->workStatus($assignment), ['active', 'leaving_soon'], true) ? 1 : 0,
                $assignment->start_date?->toDateString() ?? '',
            ))
            ->values();
    }

    public function find(int $id): ?BusinessCourierAssignment
    {
        return BusinessCourierAssignment::query()
            ->with(['business', 'courier.agency'])
            ->find($id);
    }

    public function terminate(BusinessCourierAssignment $assignment): BusinessCourierAssignment
    {
        return DB::transaction(function () use ($assignment): BusinessCourierAssignment {
            $assignment->update([
                'end_date' => now()->subDay()->toDateString(),
                'status' => 'inactive',
            ]);

            return $assignment->fresh(['business', 'courier.agency']);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function summarize(array $filters): array
    {
        $items = $this->filter($filters)
            ->map(fn (BusinessCourierAssignment $assignment) => $this->presenter->indexRow($assignment));
        $today = Carbon::today();

        return [
            'count' => $items->count(),
            'active_count' => $items->whereIn('work_status', ['active', 'leaving_soon'])->count(),
            'completed_count' => $items->where('work_status', 'completed')->count(),
            'started_this_month' => $items
                ->filter(function (array $record) use ($today) {
                    $start = Carbon::parse($record['start_date']);

                    return $start->month === $today->month && $start->year === $today->year;
                })
                ->count(),
        ];
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
        $today = Carbon::today();

        return BusinessCourierAssignment::query()
            ->when(! empty($filters['courier_id']) && $filters['courier_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('courier_id', (int) $filters['courier_id']);
            })
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = mb_strtolower((string) $filters['search']);

                $query->whereHas('courier', function (Builder $inner) use ($search): void {
                    $inner->whereRaw('LOWER(full_name) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(COALESCE(phone, "")) LIKE ?', ['%'.$search.'%']);
                });
            })
            ->when(! empty($filters['business_id']) && $filters['business_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('business_id', (int) $filters['business_id']);
            })
            ->when(! empty($filters['agency_id']) && $filters['agency_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('courier', fn (Builder $inner) => $inner->where('agency_id', (int) $filters['agency_id']));
            })
            ->when(! empty($filters['courier_type']) && $filters['courier_type'] !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('courier', fn (Builder $inner) => $inner->where('courier_type', $filters['courier_type']));
            })
            ->when(! empty($filters['date_range']) && $filters['date_range'] !== 'all', function (Builder $query) use ($filters, $today): void {
                match ($filters['date_range']) {
                    'last_7_days' => $query->whereDate('start_date', '>=', $today->copy()->subDays(7)),
                    'last_30_days' => $query->whereDate('start_date', '>=', $today->copy()->subDays(30)),
                    'this_month' => $query
                        ->whereMonth('start_date', $today->month)
                        ->whereYear('start_date', $today->year),
                    'last_3_months' => $query->whereDate('start_date', '>=', $today->copy()->subMonths(3)),
                    'this_year' => $query->whereYear('start_date', $today->year),
                    default => null,
                };
            });
    }

    /**
     * @param  Collection<int, BusinessCourierAssignment>  $assignments
     * @param  array<string, mixed>  $filters
     * @return Collection<int, BusinessCourierAssignment>
     */
    private function applyPresentationFilters(Collection $assignments, array $filters): Collection
    {
        if (empty($filters['status']) || $filters['status'] === 'all') {
            return $assignments;
        }

        return $assignments
            ->filter(function (BusinessCourierAssignment $assignment) use ($filters): bool {
                $workStatus = $this->presenter->workStatus($assignment);

                if ($filters['status'] === 'active') {
                    return in_array($workStatus, ['active', 'leaving_soon'], true);
                }

                return $workStatus === 'completed';
            })
            ->values();
    }
}
