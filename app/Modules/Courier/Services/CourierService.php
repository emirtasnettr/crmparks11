<?php

namespace App\Modules\Courier\Services;

use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Models\VehicleType;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Services\CurrentAccountService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CourierService
{
    public function __construct(
        private readonly CourierMediaService $media,
        private readonly ActivityLogService $activityLog,
        private readonly CurrentAccountService $currentAccounts,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Courier>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with(['city', 'district', 'agency', 'vehicleType'])
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $id): ?Courier
    {
        return Courier::query()
            ->with(['city', 'district', 'agency', 'vehicleType'])
            ->find($id);
    }

    public function exists(int $id): bool
    {
        return Courier::query()->whereKey($id)->exists();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function summary(array $filters): array
    {
        $items = $this->filter($filters);

        return [
            'total' => $items->count(),
            'active' => $items->where('status', 'active')->count(),
            'independent' => $items->where('courier_type', 'independent')->count(),
            'agency' => $items->where('courier_type', 'agency')->count(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function agencyOptions(): array
    {
        return \App\Modules\Agency\Models\Agency::query()
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn ($agency) => [
                'id' => $agency->id,
                'name' => $agency->displayName(),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Courier
    {
        return DB::transaction(function () use ($data, $user): Courier {
            $courier = Courier::query()->create(
                $this->courierAttributes($data, $user),
            );

            $this->syncPhoto($courier, $data['profile_photo'] ?? null);

            $this->activityLog->log(
                'courier_created',
                $courier,
                description: "{$courier->full_name} kuryesi sisteme kaydedildi.",
            );

            $this->currentAccounts->ensureForEntity($courier);

            return $courier->fresh(['city', 'district', 'agency', 'vehicleType']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Courier $courier, array $data, User $user): Courier
    {
        return DB::transaction(function () use ($courier, $data): Courier {
            $previousStatus = $courier->status;

            $courier->update(
                $this->courierAttributes($data, null, $courier),
            );

            $this->syncPhoto($courier, $data['profile_photo'] ?? null, replace: isset($data['profile_photo']));

            $courier = $courier->fresh(['city', 'district', 'agency', 'vehicleType']);

            if ($previousStatus !== $courier->status && $courier->status === 'inactive') {
                $this->activityLog->log(
                    'courier_deactivated',
                    $courier,
                    ['status' => $previousStatus],
                    ['status' => $courier->status],
                    "{$courier->full_name} pasif duruma alındı.",
                );
            } elseif ($previousStatus === 'inactive' && $courier->status === 'active') {
                $this->activityLog->log(
                    'courier_activated',
                    $courier,
                    ['status' => $previousStatus],
                    ['status' => $courier->status],
                    "{$courier->full_name} tekrar aktifleştirildi.",
                );
            } else {
                $this->activityLog->log(
                    'courier_updated',
                    $courier,
                    description: "{$courier->full_name} profil bilgileri güncellendi.",
                );
            }

            return $courier;
        });
    }

    public function deactivate(Courier $courier): Courier
    {
        $previousStatus = $courier->status;
        $courier->update(['status' => 'inactive']);
        $courier = $courier->fresh(['city', 'district', 'agency', 'vehicleType']);

        if ($previousStatus !== 'inactive') {
            $this->activityLog->log(
                'courier_deactivated',
                $courier,
                ['status' => $previousStatus],
                ['status' => $courier->status],
                "{$courier->full_name} pasif duruma alındı.",
            );
        }

        return $courier;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return Courier::query()
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = mb_strtolower((string) $filters['search']);

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereRaw('LOWER(full_name) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(tc_number) LIKE ?', ['%'.$search.'%']);
                });
            })
            ->when(! empty($filters['courier_type']) && $filters['courier_type'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('courier_type', $filters['courier_type']);
            })
            ->when(! empty($filters['agency_id']) && $filters['agency_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('agency_id', (int) $filters['agency_id']);
            })
            ->when(! empty($filters['status']) && $filters['status'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when(! empty($filters['vehicle_type']) && $filters['vehicle_type'] !== 'all', function (Builder $query) use ($filters): void {
                $vehicleTypeId = $this->resolveVehicleTypeId((string) $filters['vehicle_type']);

                if ($vehicleTypeId !== null) {
                    $query->where('vehicle_type_id', $vehicleTypeId);
                }
            });
    }

    private function resolveCityId(?string $cityName): ?int
    {
        $cityName = trim((string) $cityName);

        if ($cityName === '') {
            return null;
        }

        return City::query()->where('name', $cityName)->value('id');
    }

    private function resolveDistrictId(?string $cityName, ?string $districtName): ?int
    {
        $cityName = trim((string) $cityName);
        $districtName = trim((string) $districtName);

        if ($cityName === '' || $districtName === '') {
            return null;
        }

        $cityId = $this->resolveCityId($cityName);

        if ($cityId === null) {
            return null;
        }

        return District::query()
            ->where('city_id', $cityId)
            ->where('name', $districtName)
            ->value('id');
    }

    private function resolveVehicleTypeId(string $formCode): ?int
    {
        $dbCode = match ($formCode) {
            'motorcycle' => 'motor',
            'ebike' => 'bicycle',
            'car' => 'car',
            'bicycle' => 'bicycle',
            'pedestrian' => 'pedestrian',
            default => null,
        };

        if ($dbCode === null) {
            return null;
        }

        return VehicleType::query()->where('code', $dbCode)->value('id');
    }

    private function resolveAgencyId(mixed $agencyId, string $courierType): ?int
    {
        if ($courierType !== 'agency' || $agencyId === null || $agencyId === '') {
            return null;
        }

        return (int) $agencyId;
    }

    private function syncPhoto(Courier $courier, mixed $photo, bool $replace = true): void
    {
        if (! $replace || $photo === null) {
            return;
        }

        if (! empty($courier->photo_path)) {
            $this->media->delete($courier->photo_path);
        }

        $uploaded = $this->media->storePhoto($photo, $courier->id);
        $courier->update(['photo_path' => $uploaded['path']]);
    }

    private function generateTcNumber(): string
    {
        do {
            $candidate = (string) random_int(10_000_000_000, 99_999_999_999);
        } while (Courier::query()->where('tc_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function courierAttributes(array $data, ?User $user, ?Courier $courier = null): array
    {
        $firstName = $data['first_name'];
        $lastName = $data['last_name'];
        $courierType = $data['courier_type'];

        $attributes = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => trim($firstName.' '.$lastName),
            'phone' => $data['phone'],
            'tc_number' => $data['tc_number'] ?? $courier?->tc_number ?? $this->generateTcNumber(),
            'courier_type' => $courierType,
            'agency_id' => $this->resolveAgencyId($data['agency_id'] ?? null, $courierType),
            'vehicle_type_id' => $this->resolveVehicleTypeId($data['vehicle_type']),
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ];

        if (Schema::hasColumn('couriers', 'email')) {
            $attributes['email'] = $data['email'] ?? null;
        }

        if (Schema::hasColumn('couriers', 'birth_date')) {
            $attributes['birth_date'] = $data['birth_date'] ?? null;
        }

        if (Schema::hasColumn('couriers', 'tax_office')) {
            $attributes['tax_office'] = $data['tax_office'] ?? null;
        }

        $attributes['tax_number'] = $data['tax_number'] ?? $courier?->tax_number;
        $attributes['company_name'] = $data['company_name'] ?? $courier?->company_name;
        $attributes['iban'] = $data['iban'] ?? $courier?->iban;

        if (Schema::hasColumn('couriers', 'city_id')) {
            $attributes['city_id'] = $this->resolveCityId($data['city'] ?? null);
        }

        if (Schema::hasColumn('couriers', 'district_id')) {
            $attributes['district_id'] = $this->resolveDistrictId($data['city'] ?? null, $data['district'] ?? null);
        }

        if (Schema::hasColumn('couriers', 'address')) {
            $attributes['address'] = $data['address'] ?? null;
        }

        if (Schema::hasColumn('couriers', 'bank_name')) {
            $attributes['bank_name'] = $data['bank_name'] ?? null;
        }

        if (Schema::hasColumn('couriers', 'account_holder')) {
            $attributes['account_holder'] = $data['account_holder'] ?? null;
        }

        if (Schema::hasColumn('couriers', 'plate')) {
            $attributes['plate'] = $data['plate'] ?? null;
        }

        if (Schema::hasColumn('couriers', 'vehicle_brand')) {
            $attributes['vehicle_brand'] = $data['vehicle_brand'] ?? null;
        }

        if (Schema::hasColumn('couriers', 'vehicle_model')) {
            $attributes['vehicle_model'] = $data['vehicle_model'] ?? null;
        }

        if (Schema::hasColumn('couriers', 'start_date')) {
            $attributes['start_date'] = $data['start_date'];
        }

        if ($courier === null && $user !== null) {
            $attributes['created_by'] = $user->id;
        }

        return $attributes;
    }
}
