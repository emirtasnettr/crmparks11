<?php

namespace App\Modules\Courier\Services;

use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierVehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourierVehicleService
{
    public function __construct(
        private readonly CourierVehiclePresenter $presenter,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, CourierVehicle>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with('courier')
            ->orderByDesc('status')
            ->orderByDesc('registered_at')
            ->get()
            ->sortByDesc(fn (CourierVehicle $vehicle) => sprintf(
                '%d-%03d',
                $vehicle->status === 'active' ? 1 : 0,
                $vehicle->id,
            ))
            ->values();
    }

    public function find(int $id): ?CourierVehicle
    {
        return CourierVehicle::query()
            ->with('courier')
            ->find($id);
    }

    /**
     * @return Collection<int, CourierVehicle>
     */
    public function forCourier(int $courierId): Collection
    {
        return CourierVehicle::query()
            ->where('courier_id', $courierId)
            ->with('courier')
            ->orderByDesc('status')
            ->orderByDesc('registered_at')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        $items = CourierVehicle::query()->get();

        return [
            'count' => $items->count(),
            'motorcycle' => $items->where('vehicle_type', 'motorcycle')->count(),
            'car' => $items->where('vehicle_type', 'car')->count(),
            'active' => $items->where('status', 'active')->count(),
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
     * @return array<int, string>
     */
    public function brands(): array
    {
        return CourierVehicle::query()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CourierVehicle
    {
        return DB::transaction(function () use ($data): CourierVehicle {
            $courier = Courier::query()->findOrFail((int) $data['courier_id']);

            $vehicle = CourierVehicle::query()->create([
                'courier_id' => (int) $data['courier_id'],
                'vehicle_type' => $data['vehicle_type'],
                'plate' => $data['plate'] ?? null,
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'model_year' => $data['model_year'] ?? null,
                'color' => $data['color'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'insurance_policy_number' => $data['insurance_policy_number'] ?? null,
                'insurance_expiry_date' => $data['insurance_expiry_date'] ?? null,
                'status' => $data['status'] ?? 'active',
                'registered_at' => $data['registered_at'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->activityLog->log(
                'vehicle_added',
                $vehicle,
                description: "{$courier->full_name} için yeni araç kaydı oluşturuldu.",
            );

            return $vehicle;
        });
    }

    public function deactivate(CourierVehicle $vehicle): CourierVehicle
    {
        $vehicle->update(['status' => 'inactive']);

        return $vehicle->fresh(['courier']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return CourierVehicle::query()
            ->when(! empty($filters['courier_id']) && $filters['courier_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('courier_id', (int) $filters['courier_id']);
            })
            ->when(! empty($filters['vehicle_type']) && $filters['vehicle_type'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('vehicle_type', $filters['vehicle_type']);
            })
            ->when(! empty($filters['brand']) && $filters['brand'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('brand', $filters['brand']);
            })
            ->when(! empty($filters['status']) && $filters['status'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            });
    }
}
