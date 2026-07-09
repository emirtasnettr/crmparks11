<?php

namespace App\Modules\Agency\Services;

use App\Modules\Agency\Data\AgencyCourierFormData;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AgencyCourierService
{
    public function __construct(
        private readonly AgencyCourierPresenter $presenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with(['agency', 'vehicleType'])
            ->orderByDesc('start_date')
            ->orderBy('full_name')
            ->get()
            ->map(fn (Courier $courier) => $this->presenter->indexRow(
                $courier,
                $this->activeAssignmentFor($courier),
            ))
            ->when(
                ! empty($filters['active_business']) && $filters['active_business'] !== 'all',
                fn (Collection $items) => $items
                    ->filter(fn (array $row) => ($row['active_business_name'] ?? null) === $filters['active_business'])
                    ->values(),
            );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function summarize(array $filters): array
    {
        $items = $this->filter($filters);
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        return [
            'total' => $items->count(),
            'active' => $items->where('status', 'active')->count(),
            'inactive' => $items->where('status', 'inactive')->count(),
            'this_month' => $items->filter(function (array $row) use ($currentMonth, $currentYear): bool {
                if (empty($row['join_date'])) {
                    return false;
                }

                $date = Carbon::parse($row['join_date']);

                return (int) $date->format('n') === $currentMonth && (int) $date->format('Y') === $currentYear;
            })->count(),
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
     * @return array<int, array{id: int, name: string, phone: string}>
     */
    public function couriers(): array
    {
        return Courier::query()
            ->whereNull('agency_id')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'phone'])
            ->map(fn (Courier $courier) => [
                'id' => $courier->id,
                'name' => $courier->full_name,
                'phone' => $courier->phone ?? '—',
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function activeBusinesses(): array
    {
        return $this->filter([])
            ->pluck('active_business_name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return Courier::query()
            ->whereNotNull('agency_id')
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = mb_strtolower((string) $filters['search']);

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereRaw('LOWER(full_name) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(COALESCE(phone, "")) LIKE ?', ['%'.$search.'%']);
                });
            })
            ->when(! empty($filters['agency_id']) && $filters['agency_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('agency_id', (int) $filters['agency_id']);
            })
            ->when(! empty($filters['status']) && $filters['status'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when(! empty($filters['vehicle_type']) && $filters['vehicle_type'] !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('vehicleType', fn (Builder $inner) => $inner->where('code', $filters['vehicle_type']));
            });
    }

    private function activeAssignmentFor(Courier $courier): ?BusinessCourierAssignment
    {
        $today = now()->toDateString();

        return BusinessCourierAssignment::query()
            ->where('courier_id', $courier->id)
            ->where('status', 'active')
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->with('business')
            ->first();
    }
}
