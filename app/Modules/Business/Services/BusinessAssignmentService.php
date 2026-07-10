<?php

namespace App\Modules\Business\Services;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessAssignmentService
{
    public function __construct(
        private readonly BusinessAssignmentPresenter $presenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, BusinessCourierAssignment>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with(['business', 'courier.agency'])
            ->get()
            ->sortByDesc(fn (BusinessCourierAssignment $assignment) => sprintf(
                '%d-%s',
                $this->presenter->indexRow($assignment)['is_active_assignment'] ? 1 : 0,
                $assignment->start_date?->toDateString() ?? '',
            ))
            ->values();
    }

    /**
     * @return Collection<int, BusinessCourierAssignment>
     */
    public function forBusiness(int $businessId): Collection
    {
        return BusinessCourierAssignment::query()
            ->where('business_id', $businessId)
            ->with(['business', 'courier.agency'])
            ->orderByDesc('start_date')
            ->get();
    }

    public function find(int $id): ?BusinessCourierAssignment
    {
        return BusinessCourierAssignment::query()
            ->with(['business', 'courier.agency'])
            ->find($id);
    }

    public function countActive(): int
    {
        $today = now()->toDateString();

        return BusinessCourierAssignment::query()
            ->where('status', 'active')
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->count();
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
     * @return array<int, array{id: int, name: string, phone: string, courier_type: string, agency_id: int|null}>
     */
    public function couriers(): array
    {
        return Courier::query()
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'phone', 'courier_type', 'agency_id'])
            ->map(fn (Courier $courier) => [
                'id' => $courier->id,
                'name' => $courier->full_name,
                'phone' => $courier->phone ?? '—',
                'courier_type' => $courier->courier_type,
                'agency_id' => $courier->agency_id,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): BusinessCourierAssignment
    {
        return DB::transaction(function () use ($data, $user): BusinessCourierAssignment {
            return BusinessCourierAssignment::query()->create([
                'business_id' => (int) $data['business_id'],
                'courier_id' => (int) $data['courier_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'assigned_by' => $user->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BusinessCourierAssignment $assignment, array $data): BusinessCourierAssignment
    {
        return DB::transaction(function () use ($assignment, $data): BusinessCourierAssignment {
            $assignment->update([
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? $assignment->status,
                'notes' => $data['notes'] ?? $assignment->notes,
            ]);

            return $assignment->fresh(['business', 'courier.agency']);
        });
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
     */
    private function baseQuery(array $filters): Builder
    {
        return BusinessCourierAssignment::query()
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
            ->when(! empty($filters['status']) && $filters['status'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            });
    }
}
