<?php

namespace App\Modules\Business\Services;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
    public function forBusiness(int $businessId, bool $activeOnly = false): Collection
    {
        return BusinessCourierAssignment::query()
            ->where('business_id', $businessId)
            ->when($activeOnly, fn (Builder $query) => $query->currentlyActive())
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
        return BusinessCourierAssignment::query()
            ->currentlyActive()
            ->count();
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
     * @return array<int, array{id: int, name: string}>
     */
    public function agencies(): array
    {
        return Agency::query()
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Agency $agency) => [
                'id' => $agency->id,
                'name' => $agency->displayName(),
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
     * Atama formu için: halihazırda aktif işletmesi olmayan kuryeler.
     *
     * @return array<int, array{id: int, name: string, phone: string, courier_type: string, agency_id: int|null}>
     */
    public function couriersAvailableForAssignment(): array
    {
        $busyIds = BusinessCourierAssignment::query()
            ->currentlyActive()
            ->pluck('courier_id');

        return Courier::query()
            ->whereNotIn('id', $busyIds)
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
            $status = (string) ($data['status'] ?? 'active');
            $endDate = $data['end_date'] ?? null;

            if ($this->wouldBeCurrentlyActive($status, $endDate)) {
                $this->assertCourierHasNoOtherActiveAssignment((int) $data['courier_id']);
            }

            return BusinessCourierAssignment::query()->create([
                'business_id' => (int) $data['business_id'],
                'courier_id' => (int) $data['courier_id'],
                'start_date' => $data['start_date'],
                'end_date' => $endDate,
                'status' => $status,
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
            $status = (string) ($data['status'] ?? $assignment->status);
            $endDate = array_key_exists('end_date', $data) ? $data['end_date'] : $assignment->end_date?->toDateString();

            if ($this->wouldBeCurrentlyActive($status, $endDate)) {
                $this->assertCourierHasNoOtherActiveAssignment((int) $assignment->courier_id, (int) $assignment->id);
            }

            $assignment->update([
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'status' => $status,
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

    public function wouldBeCurrentlyActive(string $status, mixed $endDate): bool
    {
        if ($status !== 'active') {
            return false;
        }

        if ($endDate === null || $endDate === '') {
            return true;
        }

        return now()->toDateString() <= Carbon::parse((string) $endDate)->toDateString();
    }

    public function assertCourierHasNoOtherActiveAssignment(int $courierId, ?int $ignoreAssignmentId = null): void
    {
        $exists = BusinessCourierAssignment::query()
            ->currentlyActive()
            ->where('courier_id', $courierId)
            ->when($ignoreAssignmentId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreAssignmentId))
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'courier_id' => 'Bu kurye zaten bir işletmeye atanmış. Bir kurye aynı anda yalnızca bir işletmede çalışabilir.',
            ]);
        }
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
