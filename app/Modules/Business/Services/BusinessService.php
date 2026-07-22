<?php

namespace App\Modules\Business\Services;

use App\Models\City;
use App\Models\District;
use App\Models\Neighborhood;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Services\CurrentAccountService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BusinessService
{
  public function __construct(
    private readonly BusinessPresenter $presenter,
    private readonly BusinessMediaService $media,
    private readonly CurrentAccountService $currentAccounts,
  ) {}

  /**
   * @param  array<string, mixed>  $filters
   * @return Collection<int, Business>
   */
  public function filter(array $filters): Collection
  {
    return $this->baseQuery($filters)
      ->with(['city', 'district', 'neighborhood', 'activeCommercialContract'])
      ->orderByDesc('id')
      ->get();
  }

  public function find(int $id): ?Business
  {
    return Business::query()
      ->with(['city', 'district', 'neighborhood', 'activeCommercialContract'])
      ->find($id);
  }

  public function exists(int $id): bool
  {
    return Business::query()->whereKey($id)->exists();
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
   * @param  array<string, mixed>  $data
   */
  public function create(array $data, User $user): Business
  {
    return DB::transaction(function () use ($data, $user): Business {
      $business = Business::query()->create(
        $this->businessAttributes($data, $user),
      );

      $this->syncLogo($business, $data['logo'] ?? null);
      $this->currentAccounts->ensureForEntity($business);

      return $business->fresh(['city', 'district', 'neighborhood', 'activeCommercialContract']);
    });
  }

  /**
   * @param  array<string, mixed>  $data
   */
  public function update(Business $business, array $data, User $user): Business
  {
    return DB::transaction(function () use ($business, $data, $user): Business {
      $business->update(
        $this->businessAttributes($data, $user, $business),
      );

      $this->syncLogo($business, $data['logo'] ?? null, replace: isset($data['logo']));

      return $business->fresh(['city', 'district', 'neighborhood', 'activeCommercialContract']);
    });
  }

  public function deactivate(Business $business, array $data = []): Business
  {
    // Pasife alma yalnızca işletme durumunu günceller; mevcut hakediş satırlarına dokunulmaz.
    $payload = [
      'status' => 'inactive',
      'contract_end_date' => $data['contract_end_date'] ?? now()->toDateString(),
    ];

    if (array_key_exists('notes', $data)) {
      $payload['notes'] = $data['notes'];
    }

    $business->update($payload);

    return $business->fresh(['city', 'district', 'neighborhood', 'activeCommercialContract']);
  }

  public function destroy(Business $business): void
  {
    DB::transaction(function () use ($business): void {
      if (! empty($business->logo_path)) {
        $this->media->delete($business->logo_path);
      }

      $business->delete();
    });
  }

  /**
   * @param  array<string, mixed>  $filters
   */
  private function baseQuery(array $filters): Builder
  {
    return Business::query()
      ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
        $search = mb_strtolower((string) $filters['search']);

        $query->where(function (Builder $inner) use ($search): void {
          $inner->whereRaw('LOWER(company_name) LIKE ?', ['%'.$search.'%'])
            ->orWhereRaw('LOWER(COALESCE(brand_name, "")) LIKE ?', ['%'.$search.'%'])
            ->orWhereRaw('LOWER(COALESCE(phone, "")) LIKE ?', ['%'.$search.'%'])
            ->orWhere('public_id', $search);
        });
      })
      ->when(! empty($filters['status']) && $filters['status'] !== 'all', function (Builder $query) use ($filters): void {
        $query->where('status', $filters['status']);
      })
      ->when(! empty($filters['city']) && $filters['city'] !== 'all', function (Builder $query) use ($filters): void {
        $query->whereHas('city', fn (Builder $cityQuery) => $cityQuery->where('name', $filters['city']));
      })
      ->when(! empty($filters['work_type']) && $filters['work_type'] !== 'all', function (Builder $query) use ($filters): void {
        $query->whereHas(
          'activeCommercialContract',
          fn (Builder $contractQuery) => $contractQuery->where('work_type', $filters['work_type'])
        );
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

  private function resolveNeighborhoodId(?string $cityName, ?string $districtName, ?string $neighborhoodName): ?int
  {
    $neighborhoodName = trim((string) $neighborhoodName);

    if ($neighborhoodName === '') {
      return null;
    }

    $districtId = $this->resolveDistrictId($cityName, $districtName);

    if ($districtId === null) {
      return null;
    }

    return Neighborhood::query()
      ->where('district_id', $districtId)
      ->where('name', $neighborhoodName)
      ->value('id');
  }

  private function syncLogo(Business $business, mixed $logo, bool $replace = true): void
  {
    if (! $replace || $logo === null) {
      return;
    }

    if (! empty($business->logo_path)) {
      $this->media->delete($business->logo_path);
    }

    $uploaded = $this->media->storeLogo($logo, $business->id);
    $business->update(['logo_path' => $uploaded['path']]);
  }

  private function generateTaxNumber(): string
  {
    do {
      $candidate = (string) random_int(1_000_000_000, 9_999_999_999);
    } while (Business::query()->where('tax_number', $candidate)->exists());

    return $candidate;
  }

  /**
   * @param  array<string, mixed>  $data
   * @return array<string, mixed>
   */
  private function businessAttributes(array $data, User $user, ?Business $business = null): array
  {
    $attributes = [
      'company_name' => $data['company_name'],
      'brand_name' => $data['brand_name'],
      'tax_office' => $data['tax_office'] ?? '',
      'tax_number' => $data['tax_number'] ?? $business?->tax_number ?? $this->generateTaxNumber(),
      'phone' => $data['phone'],
      'email' => $data['email'] ?? null,
      'city_id' => $this->resolveCityId($data['city'] ?? null),
      'district_id' => $this->resolveDistrictId($data['city'] ?? null, $data['district'] ?? null),
      'neighborhood_id' => $this->resolveNeighborhoodId(
        $data['city'] ?? null,
        $data['district'] ?? null,
        $data['neighborhood'] ?? null,
      ),
      'address' => $data['address'] ?? null,
      'latitude' => isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null,
      'longitude' => isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null,
      'status' => $data['status'],
      'notes' => $data['notes'] ?? null,
    ];

    $status = $data['status'] ?? null;

    if ($status === 'inactive') {
      $attributes['contract_end_date'] = $data['contract_end_date'] ?? null;
    }

    if (in_array($status, ['pending', 'contract_stage'], true)) {
      $attributes['estimated_opening_date'] = $data['estimated_opening_date'] ?? null;
    }

    if ($status === 'opening_stage') {
      $attributes['start_date'] = $data['start_date'] ?? null;
    }

    if (Schema::hasColumn('businesses', 'website')) {
      $attributes['website'] = $data['website'] ?? null;
    }

    if (Schema::hasColumn('businesses', 'earning_period')) {
      $attributes['earning_period'] = $data['earning_period'] ?? null;
    }

    if (Schema::hasColumn('businesses', 'first_invoice_date')) {
      $attributes['first_invoice_date'] = filled($data['earning_period'] ?? null)
        ? ($data['first_invoice_date'] ?? null)
        : null;
    }

    if (Schema::hasColumn('businesses', 'planned_courier_count')) {
      $attributes['planned_courier_count'] = (int) ($data['planned_courier_count'] ?? 0);
    }

    if ($business === null) {
      $attributes['created_by'] = $user->id;
    }

    return $attributes;
  }
}
