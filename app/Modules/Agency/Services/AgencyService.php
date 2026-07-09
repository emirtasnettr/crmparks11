<?php

namespace App\Modules\Agency\Services;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Finance\Services\CurrentAccountService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgencyService
{
    public function __construct(
        private readonly AgencyMediaService $media,
        private readonly CurrentAccountService $currentAccounts,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Agency>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with(['city', 'district'])
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $id): ?Agency
    {
        return Agency::query()
            ->with(['city', 'district'])
            ->find($id);
    }

    public function exists(int $id): bool
    {
        return Agency::query()->whereKey($id)->exists();
    }

    /**
     * @return array<int, string>
     */
    public function cities(): array
    {
        return City::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function options(): array
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
     * @return array<string, int|float>
     */
    public function summary(array $filters): array
    {
        $items = $this->filter($filters);

        return [
            'total' => $items->count(),
            'active' => $items->where('status', 'active')->count(),
            'total_couriers' => $items->sum(fn (Agency $agency) => $agency->activeCourierCount()),
            'monthly_earnings' => 0.0,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Agency
    {
        return DB::transaction(function () use ($data, $user): Agency {
            $agency = Agency::query()->create(
                $this->agencyAttributes($data, $user),
            );

            $this->syncLogo($agency, $data['logo'] ?? null);
            $this->currentAccounts->ensureForEntity($agency);

            return $agency->fresh(['city', 'district']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Agency $agency, array $data, User $user): Agency
    {
        return DB::transaction(function () use ($agency, $data, $user): Agency {
            $agency->update(
                $this->agencyAttributes($data, $user, $agency),
            );

            $this->syncLogo($agency, $data['logo'] ?? null, replace: isset($data['logo']));

            return $agency->fresh(['city', 'district']);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return Agency::query()
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $search = mb_strtolower((string) $filters['search']);

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereRaw('LOWER(company_name) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(COALESCE(tax_number, "")) LIKE ?', ['%'.$search.'%'])
                        ->orWhereRaw('LOWER(COALESCE(phone, "")) LIKE ?', ['%'.$search.'%']);
                });
            })
            ->when(! empty($filters['status']) && $filters['status'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when(! empty($filters['city']) && $filters['city'] !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('city', fn (Builder $cityQuery) => $cityQuery->where('name', $filters['city']));
            })
            ->when(($filters['courier_count'] ?? 'all') !== 'all' && ($filters['courier_count'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $range = (string) $filters['courier_count'];
                $subquery = '(SELECT COUNT(*) FROM couriers WHERE couriers.agency_id = agencies.id AND couriers.courier_type = ? AND couriers.status = ? AND couriers.deleted_at IS NULL)';

                match ($range) {
                    '0' => $query->whereRaw("$subquery = 0", ['agency', 'active']),
                    '1-5' => $query->whereRaw("$subquery BETWEEN 1 AND 5", ['agency', 'active']),
                    '6-10' => $query->whereRaw("$subquery BETWEEN 6 AND 10", ['agency', 'active']),
                    '11-20' => $query->whereRaw("$subquery BETWEEN 11 AND 20", ['agency', 'active']),
                    '21+' => $query->whereRaw("$subquery >= 21", ['agency', 'active']),
                    default => null,
                };
            });
    }

    private function resolveCityId(?string $cityName): ?int
    {
        if ($cityName === null || $cityName === '') {
            return null;
        }

        return City::query()->where('name', $cityName)->value('id');
    }

    private function resolveDistrictId(?string $cityName, ?string $districtName): ?int
    {
        if ($cityName === null || $cityName === '' || $districtName === null || $districtName === '') {
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

    private function syncLogo(Agency $agency, mixed $logo, bool $replace = true): void
    {
        if (! $replace || $logo === null) {
            return;
        }

        if (! empty($agency->logo_path)) {
            $this->media->delete($agency->logo_path);
        }

        $uploaded = $this->media->storeLogo($logo, $agency->id);
        $agency->update(['logo_path' => $uploaded['path']]);
    }

    private function normalizeCommissionRate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(['%', ' '], '', (string) $value);
        $normalized = str_replace(',', '.', $normalized);

        return round((float) $normalized, 2);
    }

    private function generateTaxNumber(): string
    {
        do {
            $candidate = (string) random_int(1_000_000_000, 9_999_999_999);
        } while (Agency::query()->where('tax_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function agencyAttributes(array $data, User $user, ?Agency $agency = null): array
    {
        $attributes = [
            'company_name' => $data['company_name'],
            'tax_office' => $data['tax_office'] ?? '',
            'tax_number' => $data['tax_number'] ?? $agency?->tax_number ?? $this->generateTaxNumber(),
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'city_id' => $this->resolveCityId($data['city'] ?? null),
            'district_id' => $this->resolveDistrictId($data['city'] ?? null, $data['district'] ?? null),
            'address' => $data['address'] ?? null,
            'commission_rate' => $this->normalizeCommissionRate($data['commission_rate'] ?? null),
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ];

        if (Schema::hasColumn('agencies', 'brand_name')) {
            $attributes['brand_name'] = $data['brand_name'] ?? null;
        }

        if (Schema::hasColumn('agencies', 'website')) {
            $attributes['website'] = $data['website'] ?? null;
        }

        if (Schema::hasColumn('agencies', 'authorized_person')) {
            $attributes['authorized_person'] = $data['authorized_person'] ?? null;
        }

        if (Schema::hasColumn('agencies', 'mersis_number')) {
            $attributes['mersis_number'] = $data['mersis_number'] ?? null;
        }

        if (Schema::hasColumn('agencies', 'trade_registry_number')) {
            $attributes['trade_registry_number'] = $data['trade_registry_number'] ?? null;
        }

        if (Schema::hasColumn('agencies', 'payment_period')) {
            $attributes['payment_period'] = $data['payment_period'] ?? null;
        }

        if (Schema::hasColumn('agencies', 'bank_key')) {
            $attributes['bank_key'] = $data['bank_key'] ?? null;
        }

        if (Schema::hasColumn('agencies', 'account_holder')) {
            $attributes['account_holder'] = $data['account_holder'] ?? null;
        }

        if (Schema::hasColumn('agencies', 'iban')) {
            $attributes['iban'] = $data['iban'] ?? null;
        }

        if ($agency === null) {
            $attributes['created_by'] = $user->id;
        }

        return $attributes;
    }
}
