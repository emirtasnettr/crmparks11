<?php

namespace App\Modules\Business\Data;

use App\Core\Helpers\MoneyCalculator;
use App\Core\Profile\StoredProfileMerger;
use App\Modules\Business\Services\BusinessMediaService;
use App\Modules\Business\Services\BusinessProfileStore;
use App\Modules\Business\Support\BusinessFeatures;
use App\Support\DemoData;
use App\Support\PublicMediaUrl;

class BusinessDummyData
{
  /**
   * @return array<int, array<string, mixed>>
   */
  public static function all(): array
  {
    return DemoData::records([
      [
        'id' => 1,
        'logo' => 'BG',
        'logo_color' => 'bg-orange-500',
        'company_name' => 'Burger House Gıda Ltd. Şti.',
        'brand_name' => 'Burger House',
        'phone' => '0216 555 12 34',
        'city' => 'İstanbul',
        'district' => 'Kadıköy',
        'pricing_model' => 'per_package',
        'active_couriers' => 4,
        'status' => 'active',
      ],
      [
        'id' => 2,
        'logo' => 'PZ',
        'logo_color' => 'bg-red-500',
        'company_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.',
        'brand_name' => 'Napoli Pizza',
        'phone' => '0212 444 56 78',
        'city' => 'İstanbul',
        'district' => 'Beşiktaş',
        'pricing_model' => 'per_package',
        'active_couriers' => 6,
        'status' => 'active',
      ],
      [
        'id' => 3,
        'logo' => 'MK',
        'logo_color' => 'bg-emerald-500',
        'company_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.',
        'brand_name' => 'Yeşil Market',
        'phone' => '0312 333 44 55',
        'city' => 'Ankara',
        'district' => 'Çankaya',
        'pricing_model' => 'fixed',
        'active_couriers' => 2,
        'status' => 'active',
      ],
      [
        'id' => 4,
        'logo' => 'ET',
        'logo_color' => 'bg-blue-500',
        'company_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.',
        'brand_name' => 'HızlıAl',
        'phone' => '0850 222 33 44',
        'city' => 'İzmir',
        'district' => 'Bornova',
        'pricing_model' => 'per_package',
        'active_couriers' => 12,
        'status' => 'active',
      ],
      [
        'id' => 5,
        'logo' => 'KF',
        'logo_color' => 'bg-amber-700',
        'company_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.',
        'brand_name' => 'Kahve Durağı',
        'phone' => '0232 777 88 99',
        'city' => 'İzmir',
        'district' => 'Karşıyaka',
        'pricing_model' => 'hourly',
        'active_couriers' => 3,
        'status' => 'active',
      ],
      [
        'id' => 6,
        'logo' => 'TL',
        'logo_color' => 'bg-pink-500',
        'company_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri',
        'brand_name' => 'Tatlı Diyarı',
        'phone' => '0224 666 77 88',
        'city' => 'Bursa',
        'district' => 'Nilüfer',
        'pricing_model' => 'daily',
        'active_couriers' => 2,
        'status' => 'inactive',
      ],
      [
        'id' => 7,
        'logo' => 'KS',
        'logo_color' => 'bg-rose-600',
        'company_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.',
        'brand_name' => 'Usta Kasap',
        'phone' => '0242 111 22 33',
        'city' => 'Antalya',
        'district' => 'Muratpaşa',
        'pricing_model' => 'fixed',
        'active_couriers' => 1,
        'status' => 'active',
      ],
      [
        'id' => 8,
        'logo' => 'MN',
        'logo_color' => 'bg-lime-600',
        'company_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.',
        'brand_name' => 'Taze Manav',
        'phone' => '0216 999 00 11',
        'city' => 'İstanbul',
        'district' => 'Ümraniye',
        'pricing_model' => 'daily',
        'active_couriers' => 0,
        'status' => 'inactive',
      ],
    ]);
  }

  /**
   * @return array<int, string>
   */
  public static function cities(): array
  {
    return collect(self::all())
      ->pluck('city')
      ->unique()
      ->sort()
      ->values()
      ->all();
  }

  /**
   * @param  array<string, mixed>  $filters
   * @return array<int, array<string, mixed>>
   */
  public static function filter(array $filters): array
  {
    return collect(self::all())
      ->map(fn (array $business) => self::mergeStoredProfile((int) $business['id'], $business))
      ->filter(function (array $business) use ($filters) {
        if (! empty($filters['search'])) {
          $search = mb_strtolower($filters['search']);
          $haystack = mb_strtolower(implode(' ', [
            $business['company_name'],
            $business['brand_name'] ?? '',
            $business['phone'],
          ]));

          if (! str_contains($haystack, $search)) {
            return false;
          }
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
          if ($business['status'] !== $filters['status']) {
            return false;
          }
        }

        if (! empty($filters['city']) && $filters['city'] !== 'all') {
          if ($business['city'] !== $filters['city']) {
            return false;
          }
        }

        if (! empty($filters['pricing_model']) && $filters['pricing_model'] !== 'all') {
          if ($business['pricing_model'] !== $filters['pricing_model']) {
            return false;
          }
        }

        return true;
      })
      ->values()
      ->all();
  }

  public static function exists(int $id): bool
  {
    return collect(self::all())->contains(fn (array $business) => (int) $business['id'] === $id);
  }

  /**
   * @param  array<string, mixed>  $business
   * @return array<string, mixed>
   */
  public static function mergeStoredProfile(int $id, array $business): array
  {
    $stored = BusinessProfileStore::get($id);

    if ($stored === []) {
      return $business;
    }

    $business = StoredProfileMerger::apply($business, $stored, [
      'company_name',
      'brand_name',
      'phone',
      'email',
      'website',
      'tax_office',
      'tax_number',
      'city',
      'district',
      'address',
      'pricing_model',
      'customer_price',
      'courier_price',
      'earning_period',
      'status',
      'notes',
    ]);

    if (! empty($stored['pricing_model'])) {
      $business['pricing_model'] = $stored['pricing_model'] === 'monthly_fixed'
        ? 'fixed'
        : $stored['pricing_model'];
    }

    if (! empty($stored['logo_path']) || ! empty($stored['logo_url'])) {
      $media = app(BusinessMediaService::class);
      $business['logo_path'] = $stored['logo_path'] ?? null;
      $business['logo_url'] = ! empty($stored['logo_path'])
        ? $media->url($stored['logo_path'])
        : PublicMediaUrl::normalize($stored['logo_url'] ?? null);
      $business['has_logo_image'] = ! empty($business['logo_url']);
    }

    return $business;
  }

  /**
   * @return array<string, mixed>|null
   */
  public static function find(int $id): ?array
  {
    $business = collect(self::all())->firstWhere('id', $id);

    if ($business === null) {
      return null;
    }

    return self::mergeStoredProfile($id, $business);
  }

  /**
   * @return array{revenue_unit: float, courier_unit: float, from_profile: bool}
   */
  public static function unitPrices(int $id, ?array $business = null): array
  {
    $business ??= self::find($id);

    if ($business === null) {
      return [
        'revenue_unit' => 0.0,
        'courier_unit' => 0.0,
        'from_profile' => false,
      ];
    }

    $stored = BusinessProfileStore::get($id);

    if (
      isset($stored['customer_price'], $stored['courier_price'])
      && $stored['customer_price'] !== ''
      && $stored['courier_price'] !== ''
    ) {
      return [
        'revenue_unit' => (float) $stored['customer_price'],
        'courier_unit' => (float) $stored['courier_price'],
        'from_profile' => true,
      ];
    }

    $defaults = self::defaultUnitPrices($business['pricing_model'] ?? 'per_package');

    return [
      'revenue_unit' => $defaults['revenue_unit'],
      'courier_unit' => $defaults['courier_unit'],
      'from_profile' => false,
    ];
  }

  public static function formatStoredPrice(float $amount, string $pricingModel): string
  {
    $formatted = MoneyCalculator::formatVatAmount($amount);

    return match ($pricingModel) {
      'hourly' => $formatted.' / saat',
      'daily' => $formatted.' / gün',
      default => $formatted,
    };
  }

  /**
   * @return array{revenue_unit: float, courier_unit: float}
   */
  private static function defaultUnitPrices(string $pricingModel): array
  {
    return match ($pricingModel) {
      'per_package' => ['revenue_unit' => 45.0, 'courier_unit' => 32.0],
      'fixed', 'monthly_fixed' => ['revenue_unit' => 52.0, 'courier_unit' => 41.0],
      'hourly' => ['revenue_unit' => 38.0, 'courier_unit' => 29.0],
      'daily' => ['revenue_unit' => 40.0, 'courier_unit' => 31.0],
      default => ['revenue_unit' => 42.0, 'courier_unit' => 33.0],
    };
  }

  /**
   * @param  array<string, mixed>  $business
   * @return array<string, mixed>
   */
  public static function indexRow(array $business): array
  {
    $id = (int) $business['id'];
    $pricingModel = $business['pricing_model'] ?? 'per_package';
    $unitPrices = self::unitPrices($id, $business);

    return array_merge($business, [
      'customer_price_label' => self::formatStoredPrice($unitPrices['revenue_unit'], $pricingModel),
      'courier_price_label' => self::formatStoredPrice($unitPrices['courier_unit'], $pricingModel),
    ]);
  }

  /**
   * @param  array<string, mixed>  $business
   * @return array<string, mixed>
   */
  public static function detailPayload(array $business): array
  {
    $pricingLabels = [
      'per_package' => 'Paket Başı',
      'fixed' => 'Sabit Ücret',
      'monthly_fixed' => 'Aylık Sabit',
      'hourly' => 'Saatlik',
      'daily' => 'Günlük',
    ];

    $statusLabels = BusinessFormData::statuses();

    $id = (int) $business['id'];

    return array_merge([
      'id' => $id,
      'logo' => $business['logo'],
      'logo_color' => $business['logo_color'],
      'logo_url' => $business['logo_url'] ?? null,
      'has_logo_image' => (bool) ($business['has_logo_image'] ?? false),
      'company_name' => $business['company_name'],
      'brand_name' => $business['brand_name'],
      'phone' => $business['phone'],
      'location' => $business['city'].' / '.$business['district'],
      'pricing_model_label' => $pricingLabels[$business['pricing_model']] ?? $business['pricing_model'],
      'active_couriers' => $business['active_couriers'],
      'status' => $business['status'],
      'status_label' => $statusLabels[$business['status']] ?? $business['status'],
      'contacts_url' => route('businesses.contacts.index', ['business_id' => $id]),
      'contracts_url' => route('businesses.contracts.index', ['business_id' => $id]),
      'assignments_url' => route('businesses.assignments.index', ['business_id' => $id]),
      'documents_url' => route('businesses.documents.index', ['business_id' => $id]),
      'activities_url' => route('businesses.activities.index', ['business_id' => $id]),
    ], BusinessFeatures::earningsEnabled() ? [
      'earnings_url' => route('businesses.earnings.index', ['business_id' => $id]),
    ] : []);
  }

  /**
   * @return array<string, mixed>|null
   */
  public static function showPayload(int $id): ?array
  {
    $business = self::find($id);

    if ($business === null) {
      return null;
    }

    $slug = \Illuminate\Support\Str::slug($business['brand_name'] ?? 'isletme');
    $pricing = match ($business['pricing_model']) {
      'per_package' => ['customer_price' => '₺45,00', 'courier_price' => '₺32,00', 'earning_period_label' => 'Haftalık'],
      'fixed' => ['customer_price' => '₺15.000,00', 'courier_price' => '₺12.000,00', 'earning_period_label' => 'Aylık'],
      'hourly' => ['customer_price' => '₺120,00 / saat', 'courier_price' => '₺85,00 / saat', 'earning_period_label' => 'Haftalık'],
      'daily' => ['customer_price' => '₺800,00 / gün', 'courier_price' => '₺600,00 / gün', 'earning_period_label' => 'Haftalık'],
      default => ['customer_price' => '₺10.000,00', 'courier_price' => '₺8.000,00', 'earning_period_label' => 'Aylık'],
    };

    $stored = BusinessProfileStore::get($id);
    $unitPrices = self::unitPrices($id, $business);
    $pricingModel = $business['pricing_model'] ?? 'per_package';
    $earningPeriods = BusinessFormData::earningPeriods();

    $customerPrice = $unitPrices['from_profile']
      ? self::formatStoredPrice($unitPrices['revenue_unit'], $pricingModel)
      : $pricing['customer_price'];

    $courierPrice = $unitPrices['from_profile']
      ? self::formatStoredPrice($unitPrices['courier_unit'], $pricingModel)
      : $pricing['courier_price'];

    $earningPeriodLabel = ! empty($stored['earning_period'])
      ? ($earningPeriods[$stored['earning_period']] ?? $pricing['earning_period_label'])
      : $pricing['earning_period_label'];

    return array_merge(self::detailPayload($business), [
      'uuid' => 'biz-'.str_pad((string) $id, 3, '0', STR_PAD_LEFT),
      'email' => $stored['email'] ?? ($slug.'@ornek.com'),
      'website' => $stored['website'] ?? ('https://www.'.$slug.'.com.tr'),
      'tax_office' => $stored['tax_office'] ?? ($business['city'].' Vergi Dairesi'),
      'tax_number' => $stored['tax_number'] ?? (string) (1000000000 + $id),
      'address' => $stored['address'] ?? ('Örnek Mah. Kurye Cad. No:'.($id * 3).' '.$business['district'].' / '.$business['city']),
      'customer_price' => $customerPrice,
      'courier_price' => $courierPrice,
      'notes' => $stored['notes'] ?? 'Sistemde kayıtlı işletme profili. Operasyon notları burada görüntülenir.',
      'created_at_formatted' => now()->subMonths(12 - min($id, 11))->format('d.m.Y'),
      'contacts' => BusinessContactDummyData::filter(['business_id' => $id]),
      'contracts' => BusinessContractDummyData::filter(['business_id' => $id]),
      'assignments' => BusinessAssignmentDummyData::filter(['business_id' => $id]),
      'documents' => BusinessDocumentDummyData::filter(['business_id' => $id]),
      'activities' => BusinessActivityDummyData::filter(['business_id' => $id]),
    ], BusinessFeatures::earningsEnabled() ? [
      'earning_period_label' => $earningPeriodLabel,
      'earnings' => BusinessEarningDummyData::filter(['business_id' => $id]),
    ] : []);
  }

  /**
   * @return array<string, mixed>|null
   */
  public static function formPayload(int $id): ?array
  {
    $business = self::find($id);

    if ($business === null) {
      return null;
    }

    $slug = \Illuminate\Support\Str::slug($business['brand_name'] ?? 'isletme');
    $pricing = match ($business['pricing_model']) {
      'per_package' => ['customer_price' => '45.00', 'courier_price' => '32.00', 'earning_period' => 'weekly'],
      'fixed' => ['customer_price' => '15000.00', 'courier_price' => '12000.00', 'earning_period' => 'monthly'],
      'hourly' => ['customer_price' => '120.00', 'courier_price' => '85.00', 'earning_period' => 'weekly'],
      'daily' => ['customer_price' => '800.00', 'courier_price' => '600.00', 'earning_period' => 'weekly'],
      default => ['customer_price' => '10000.00', 'courier_price' => '8000.00', 'earning_period' => 'monthly'],
    };

    $payload = [
      'company_name' => $business['company_name'],
      'brand_name' => $business['brand_name'],
      'phone' => $business['phone'],
      'email' => $slug.'@ornek.com',
      'website' => 'https://www.'.$slug.'.com.tr',
      'tax_office' => $business['city'].' Vergi Dairesi',
      'tax_number' => (string) (1000000000 + $id),
      'city' => $business['city'],
      'district' => $business['district'],
      'address' => 'Örnek Mah. Kurye Cad. No:'.($id * 3).' '.$business['district'].' / '.$business['city'],
      'pricing_model' => $business['pricing_model'] === 'fixed' ? 'monthly_fixed' : $business['pricing_model'],
      'customer_price' => $pricing['customer_price'],
      'courier_price' => $pricing['courier_price'],
      'earning_period' => $pricing['earning_period'],
      'status' => $business['status'],
      'notes' => 'Sistemde kayıtlı işletme profili. Operasyon notları burada görüntülenir.',
      'logo_url' => null,
    ];

    $stored = BusinessProfileStore::get($id);

    if ($stored !== []) {
      $payload = array_merge($payload, array_filter([
        'company_name' => $stored['company_name'] ?? null,
        'brand_name' => $stored['brand_name'] ?? null,
        'phone' => $stored['phone'] ?? null,
        'email' => $stored['email'] ?? null,
        'website' => $stored['website'] ?? null,
        'tax_office' => $stored['tax_office'] ?? null,
        'tax_number' => $stored['tax_number'] ?? null,
        'city' => $stored['city'] ?? null,
        'district' => $stored['district'] ?? null,
        'address' => $stored['address'] ?? null,
        'pricing_model' => $stored['pricing_model'] ?? null,
        'customer_price' => $stored['customer_price'] ?? null,
        'courier_price' => $stored['courier_price'] ?? null,
        'earning_period' => $stored['earning_period'] ?? null,
        'status' => $stored['status'] ?? null,
        'notes' => $stored['notes'] ?? null,
        'logo_url' => ! empty($stored['logo_path'])
          ? app(BusinessMediaService::class)->url($stored['logo_path'])
          : PublicMediaUrl::normalize($stored['logo_url'] ?? null),
      ], fn ($value) => $value !== null && $value !== ''));
    }

    return $payload;
  }
}
