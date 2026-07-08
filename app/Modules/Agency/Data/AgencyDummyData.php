<?php

namespace App\Modules\Agency\Data;

use App\Support\DemoData;
use App\Core\Helpers\MoneyCalculator;
use App\Core\Profile\StoredProfileMerger;
use App\Modules\Agency\Data\AgencyFormData;
use App\Modules\Agency\Services\AgencyMediaService;
use App\Modules\Agency\Services\AgencyProfileStore;
use App\Modules\Agency\Support\AgencyFeatures;
use App\Support\PublicMediaUrl;
use App\Modules\Courier\Data\CourierDummyData;

class AgencyDummyData
{
    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'pending' => 'Beklemede',
            'inactive' => 'Pasif',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function courierCountRanges(): array
    {
        return [
            '0' => '0 Kurye',
            '1-5' => '1 – 5 Kurye',
            '6-10' => '6 – 10 Kurye',
            '11-20' => '11 – 20 Kurye',
            '21+' => '21+ Kurye',
        ];
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
     * Lightweight list for dropdowns (id + name).
     *
     * @return array<int, array{id: int, name: string}>
     */
    public static function options(): array
    {
        return collect(self::raw())
            ->map(fn (array $agency) => [
                'id' => $agency['id'],
                'name' => $agency['company_name'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(self::raw())
            ->map(fn (array $agency) => self::enrich(self::mergeStoredProfile((int) $agency['id'], $agency)))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function raw(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return [
            ['id' => 1, 'company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.', 'tax_number' => '1234567890', 'phone' => '0216 444 10 01', 'email' => 'info@hizlikurye.com', 'city' => 'İstanbul', 'district' => 'Ümraniye', 'authorized_person' => 'Serkan Yılmaz', 'status' => 'active', 'logo' => 'HK', 'logo_color' => 'bg-blue-600', 'active_couriers' => 5, 'active_businesses' => 4, 'monthly_earning' => 187500.00],
            ['id' => 2, 'company_name' => 'Metro Lojistik Acente A.Ş.', 'tax_number' => '2345678901', 'phone' => '0212 444 20 02', 'email' => 'iletisim@metrologistik.com', 'city' => 'İstanbul', 'district' => 'Bağcılar', 'authorized_person' => 'Ayşe Korkmaz', 'status' => 'active', 'logo' => 'ML', 'logo_color' => 'bg-indigo-600', 'active_couriers' => 5, 'active_businesses' => 3, 'monthly_earning' => 156200.00],
            ['id' => 3, 'company_name' => 'Express Dağıtım Acentesi', 'tax_number' => '3456789012', 'phone' => '0232 444 30 03', 'email' => 'destek@expressdagitim.com', 'city' => 'İzmir', 'district' => 'Bornova', 'authorized_person' => 'Mehmet Arslan', 'status' => 'active', 'logo' => 'ED', 'logo_color' => 'bg-emerald-600', 'active_couriers' => 4, 'active_businesses' => 3, 'monthly_earning' => 124800.00],
            ['id' => 4, 'company_name' => 'Anadolu Kurye Hizmetleri Ltd. Şti.', 'tax_number' => '4567890123', 'phone' => '0312 555 40 04', 'email' => 'info@anadolukurye.com', 'city' => 'Ankara', 'district' => 'Yenimahalle', 'authorized_person' => 'Fatma Çelik', 'status' => 'active', 'logo' => 'AK', 'logo_color' => 'bg-red-600', 'active_couriers' => 12, 'active_businesses' => 6, 'monthly_earning' => 298400.00],
            ['id' => 5, 'company_name' => 'Bursa Ekspres Lojistik A.Ş.', 'tax_number' => '5678901234', 'phone' => '0224 555 50 05', 'email' => 'operasyon@bursaekspres.com', 'city' => 'Bursa', 'district' => 'Nilüfer', 'authorized_person' => 'Oğuz Demir', 'status' => 'active', 'logo' => 'BE', 'logo_color' => 'bg-amber-600', 'active_couriers' => 8, 'active_businesses' => 5, 'monthly_earning' => 201600.00],
            ['id' => 6, 'company_name' => 'Akdeniz Dağıtım Acentesi', 'tax_number' => '6789012345', 'phone' => '0242 555 60 06', 'email' => 'info@akdenizdagitim.com', 'city' => 'Antalya', 'district' => 'Muratpaşa', 'authorized_person' => 'Deniz Aydın', 'status' => 'pending', 'logo' => 'AD', 'logo_color' => 'bg-cyan-600', 'active_couriers' => 3, 'active_businesses' => 2, 'monthly_earning' => 68400.00],
            ['id' => 7, 'company_name' => 'Çukurova Kurye Ltd. Şti.', 'tax_number' => '7890123456', 'phone' => '0322 555 70 07', 'email' => 'iletisim@cukurovakurye.com', 'city' => 'Adana', 'district' => 'Seyhan', 'authorized_person' => 'Hakan Şahin', 'status' => 'active', 'logo' => 'ÇK', 'logo_color' => 'bg-orange-600', 'active_couriers' => 9, 'active_businesses' => 4, 'monthly_earning' => 178300.00],
            ['id' => 8, 'company_name' => 'Gaziantep Hızlı Teslimat A.Ş.', 'tax_number' => '8901234567', 'phone' => '0342 555 80 08', 'email' => 'info@gaziantephizli.com', 'city' => 'Gaziantep', 'district' => 'Şehitkamil', 'authorized_person' => 'Zeynep Koç', 'status' => 'active', 'logo' => 'GH', 'logo_color' => 'bg-violet-600', 'active_couriers' => 7, 'active_businesses' => 3, 'monthly_earning' => 142500.00],
            ['id' => 9, 'company_name' => 'Konya Merkez Lojistik', 'tax_number' => '9012345678', 'phone' => '0332 555 90 09', 'email' => 'destek@konyamerkez.com', 'city' => 'Konya', 'district' => 'Selçuklu', 'authorized_person' => 'Burak Tunç', 'status' => 'inactive', 'logo' => 'KM', 'logo_color' => 'bg-slate-600', 'active_couriers' => 0, 'active_businesses' => 0, 'monthly_earning' => 0.00],
            ['id' => 10, 'company_name' => 'Mersin Sahil Kurye Ltd. Şti.', 'tax_number' => '1123456789', 'phone' => '0324 555 10 10', 'email' => 'info@mersinsahil.com', 'city' => 'Mersin', 'district' => 'Yenişehir', 'authorized_person' => 'Selin Erdoğan', 'status' => 'active', 'logo' => 'MS', 'logo_color' => 'bg-teal-600', 'active_couriers' => 6, 'active_businesses' => 4, 'monthly_earning' => 135900.00],
            ['id' => 11, 'company_name' => 'Kayseri Dağıtım Merkezi A.Ş.', 'tax_number' => '2234567890', 'phone' => '0352 555 11 11', 'email' => 'operasyon@kayseridagitim.com', 'city' => 'Kayseri', 'district' => 'Melikgazi', 'authorized_person' => 'Tolga Uçar', 'status' => 'active', 'logo' => 'KD', 'logo_color' => 'bg-rose-600', 'active_couriers' => 11, 'active_businesses' => 5, 'monthly_earning' => 224700.00],
            ['id' => 12, 'company_name' => 'Eskişehir Ekspres Kurye', 'tax_number' => '3345678901', 'phone' => '0222 555 12 12', 'email' => 'info@eskisehirekspres.com', 'city' => 'Eskişehir', 'district' => 'Tepebaşı', 'authorized_person' => 'Can Öztürk', 'status' => 'pending', 'logo' => 'EE', 'logo_color' => 'bg-lime-600', 'active_couriers' => 2, 'active_businesses' => 1, 'monthly_earning' => 42800.00],
            ['id' => 13, 'company_name' => 'Karadeniz Lojistik Acentesi Ltd. Şti.', 'tax_number' => '4456789012', 'phone' => '0462 555 13 13', 'email' => 'iletisim@karadenizlojistik.com', 'city' => 'Trabzon', 'district' => 'Ortahisar', 'authorized_person' => 'Emre Polat', 'status' => 'active', 'logo' => 'KL', 'logo_color' => 'bg-sky-600', 'active_couriers' => 4, 'active_businesses' => 2, 'monthly_earning' => 91200.00],
            ['id' => 14, 'company_name' => 'Samsun Hızlı Dağıtım A.Ş.', 'tax_number' => '5567890123', 'phone' => '0362 555 14 14', 'email' => 'destek@samsunhizli.com', 'city' => 'Samsun', 'district' => 'Atakum', 'authorized_person' => 'Gizem Yalçın', 'status' => 'active', 'logo' => 'SH', 'logo_color' => 'bg-fuchsia-600', 'active_couriers' => 5, 'active_businesses' => 3, 'monthly_earning' => 108600.00],
            ['id' => 15, 'company_name' => 'Denizli Paket Servis Acentesi', 'tax_number' => '6678901234', 'phone' => '0258 555 15 15', 'email' => 'info@denizlipaket.com', 'city' => 'Denizli', 'district' => 'Pamukkale', 'authorized_person' => 'Kaan Başaran', 'status' => 'active', 'logo' => 'DP', 'logo_color' => 'bg-pink-600', 'active_couriers' => 3, 'active_businesses' => 2, 'monthly_earning' => 75600.00],
            ['id' => 16, 'company_name' => 'Muğla Turizm Kurye Ltd. Şti.', 'tax_number' => '7789012345', 'phone' => '0252 555 16 16', 'email' => 'operasyon@muglaturizm.com', 'city' => 'Muğla', 'district' => 'Bodrum', 'authorized_person' => 'Leyla Güneş', 'status' => 'active', 'logo' => 'MT', 'logo_color' => 'bg-yellow-600', 'active_couriers' => 14, 'active_businesses' => 7, 'monthly_earning' => 312800.00],
            ['id' => 17, 'company_name' => 'Kocaeli Sanayi Lojistik A.Ş.', 'tax_number' => '8890123456', 'phone' => '0262 555 17 17', 'email' => 'info@kocaelisanayi.com', 'city' => 'Kocaeli', 'district' => 'Gebze', 'authorized_person' => 'Mert Akın', 'status' => 'active', 'logo' => 'KS', 'logo_color' => 'bg-stone-600', 'active_couriers' => 18, 'active_businesses' => 8, 'monthly_earning' => 389500.00],
            ['id' => 18, 'company_name' => 'Sakarya Ekspres Dağıtım', 'tax_number' => '9901234567', 'phone' => '0264 555 18 18', 'email' => 'destek@sakaryaekspres.com', 'city' => 'Sakarya', 'district' => 'Serdivan', 'authorized_person' => 'Nazlı Tekin', 'status' => 'pending', 'logo' => 'SE', 'logo_color' => 'bg-purple-600', 'active_couriers' => 1, 'active_businesses' => 1, 'monthly_earning' => 22400.00],
            ['id' => 19, 'company_name' => 'Balıkesir Kurye Hizmetleri Ltd. Şti.', 'tax_number' => '1012345678', 'phone' => '0266 555 19 19', 'email' => 'info@balikesirkurye.com', 'city' => 'Balıkesir', 'district' => 'Karesi', 'authorized_person' => 'Onur Sarı', 'status' => 'inactive', 'logo' => 'BK', 'logo_color' => 'bg-neutral-600', 'active_couriers' => 0, 'active_businesses' => 0, 'monthly_earning' => 0.00],
            ['id' => 20, 'company_name' => 'Tekirdağ Marmara Lojistik A.Ş.', 'tax_number' => '2123456789', 'phone' => '0282 555 20 20', 'email' => 'iletisim@tekirdagmarmara.com', 'city' => 'Tekirdağ', 'district' => 'Çorlu', 'authorized_person' => 'Pınar Vural', 'status' => 'active', 'logo' => 'TM', 'logo_color' => 'bg-blue-500', 'active_couriers' => 10, 'active_businesses' => 5, 'monthly_earning' => 198200.00],
            ['id' => 21, 'company_name' => 'Hatay Güney Dağıtım Acentesi', 'tax_number' => '3234567890', 'phone' => '0326 555 21 21', 'email' => 'info@hatayguney.com', 'city' => 'Hatay', 'district' => 'Antakya', 'authorized_person' => 'Rıza Duman', 'status' => 'active', 'logo' => 'HG', 'logo_color' => 'bg-green-600', 'active_couriers' => 6, 'active_businesses' => 3, 'monthly_earning' => 127400.00],
            ['id' => 22, 'company_name' => 'Malatya Doğu Ekspres Ltd. Şti.', 'tax_number' => '4345678901', 'phone' => '0422 555 22 22', 'email' => 'destek@malatyadogu.com', 'city' => 'Malatya', 'district' => 'Battalgazi', 'authorized_person' => 'Seda Işık', 'status' => 'active', 'logo' => 'MD', 'logo_color' => 'bg-orange-500', 'active_couriers' => 4, 'active_businesses' => 2, 'monthly_earning' => 86400.00],
            ['id' => 23, 'company_name' => 'Van Doğu Kurye A.Ş.', 'tax_number' => '5456789012', 'phone' => '0432 555 23 23', 'email' => 'operasyon@vandogu.com', 'city' => 'Van', 'district' => 'İpekyolu', 'authorized_person' => 'Umut Karaca', 'status' => 'pending', 'logo' => 'VD', 'logo_color' => 'bg-indigo-500', 'active_couriers' => 2, 'active_businesses' => 1, 'monthly_earning' => 38200.00],
            ['id' => 24, 'company_name' => 'Edirne Sınır Lojistik Ltd. Şti.', 'tax_number' => '6567890123', 'phone' => '0284 555 24 24', 'email' => 'info@edirnesinir.com', 'city' => 'Edirne', 'district' => 'Merkez', 'authorized_person' => 'Volkan Arslan', 'status' => 'active', 'logo' => 'ES', 'logo_color' => 'bg-cyan-500', 'active_couriers' => 3, 'active_businesses' => 2, 'monthly_earning' => 69800.00],
            ['id' => 25, 'company_name' => 'Aydın Ege Dağıtım Acentesi', 'tax_number' => '7678901234', 'phone' => '0256 555 25 25', 'email' => 'iletisim@aydinege.com', 'city' => 'Aydın', 'district' => 'Efeler', 'authorized_person' => 'Yasin Sezer', 'status' => 'active', 'logo' => 'AE', 'logo_color' => 'bg-emerald-500', 'active_couriers' => 22, 'active_businesses' => 9, 'monthly_earning' => 425600.00],
        ];
    }

    /**
     * @param  array<string, mixed>  $agency
     * @return array<string, mixed>
     */
    public static function enrich(array $agency): array
    {
        $courierCount = self::resolveActiveCourierCount($agency);

        return array_merge($agency, [
            'uuid' => 'agcy-'.str_pad((string) $agency['id'], 3, '0', STR_PAD_LEFT),
            'active_couriers' => $courierCount,
            'location' => $agency['city'].' / '.$agency['district'],
            'status_label' => self::statuses()[$agency['status']] ?? $agency['status'],
            'monthly_earning_formatted' => MoneyCalculator::format((float) $agency['monthly_earning']),
        ]);
    }

    /**
     * İleride veritabanından otomatik hesaplanacak; şimdilik kayıtlı acenteler için kurye sayısını doğrular.
     */
    public static function resolveActiveCourierCount(array $agency): int
    {
        $computed = collect(CourierDummyData::raw())
            ->filter(fn (array $courier) => (int) ($courier['agency_id'] ?? 0) === (int) $agency['id']
                && $courier['courier_type'] === 'agency'
                && $courier['status'] === 'active')
            ->count();

        return $computed > 0 ? $computed : (int) $agency['active_couriers'];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int|float>
     */
    public static function summary(array $filters = []): array
    {
        $items = self::filter($filters);

        return [
            'total' => count($items),
            'active' => collect($items)->where('status', 'active')->count(),
            'total_couriers' => collect($items)->sum('active_couriers'),
            'monthly_earnings' => collect($items)->sum('monthly_earning'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        return collect(self::all())
            ->filter(function (array $agency) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $agency['company_name'],
                        $agency['tax_number'],
                        $agency['phone'],
                    ]));

                    if (! str_contains($haystack, $search)) {
                        return false;
                    }
                }

                if (! empty($filters['city']) && $filters['city'] !== 'all') {
                    if ($agency['city'] !== $filters['city']) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($agency['status'] !== $filters['status']) {
                        return false;
                    }
                }

                if (($filters['courier_count'] ?? 'all') !== 'all' && ($filters['courier_count'] ?? '') !== '') {
                    if (! self::matchesCourierCountRange((int) $agency['active_couriers'], $filters['courier_count'])) {
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
        return collect(self::all())->contains(fn (array $agency) => (int) $agency['id'] === $id);
    }

    /**
     * @param  array<string, mixed>  $agency
     * @return array<string, mixed>
     */
    public static function mergeStoredProfile(int $id, array $agency): array
    {
        $stored = AgencyProfileStore::get($id);

        if ($stored === []) {
            return $agency;
        }

        $agency = StoredProfileMerger::apply($agency, $stored, [
            'company_name',
            'brand_name',
            'phone',
            'email',
            'website',
            'tax_office',
            'tax_number',
            'mersis_number',
            'trade_registry_number',
            'city',
            'district',
            'address',
            'commission_rate',
            'payment_period',
            'bank_key',
            'account_holder',
            'iban',
            'status',
            'notes',
            'authorized_person',
        ]);

        if (! empty($stored['logo_path']) || ! empty($stored['logo_url'])) {
            $media = app(AgencyMediaService::class);
            $agency['logo_path'] = $stored['logo_path'] ?? null;
            $agency['logo_url'] = ! empty($stored['logo_path'])
                ? $media->url($stored['logo_path'])
                : PublicMediaUrl::normalize($stored['logo_url'] ?? null);
            $agency['has_logo_image'] = ! empty($agency['logo_url']);
        }

        return $agency;
    }

    private static function matchesCourierCountRange(int $count, string $range): bool
    {
        return match ($range) {
            '0' => $count === 0,
            '1-5' => $count >= 1 && $count <= 5,
            '6-10' => $count >= 6 && $count <= 10,
            '11-20' => $count >= 11 && $count <= 20,
            '21+' => $count >= 21,
            default => true,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $agency = collect(self::raw())->firstWhere('id', $id);

        if ($agency === null) {
            return null;
        }

        return self::enrich(self::mergeStoredProfile($id, $agency));
    }

    /**
     * @param  array<string, mixed>  $agency
     * @return array<string, mixed>
     */
    public static function detailPayload(array $agency): array
    {
        $id = (int) $agency['id'];

        return array_merge([
            'id' => $id,
            'logo' => $agency['logo'],
            'logo_color' => $agency['logo_color'],
            'logo_url' => $agency['logo_url'] ?? null,
            'has_logo_image' => (bool) ($agency['has_logo_image'] ?? false),
            'company_name' => $agency['company_name'],
            'authorized_person' => $agency['authorized_person'],
            'phone' => $agency['phone'],
            'email' => $agency['email'],
            'location' => $agency['location'] ?? ($agency['city'].' / '.$agency['district']),
            'active_couriers' => $agency['active_couriers'],
            'active_businesses' => $agency['active_businesses'],
            'status_label' => $agency['status_label'] ?? (self::statuses()[$agency['status']] ?? $agency['status']),
            'contacts_url' => route('agencies.contacts.index', ['agency_id' => $id]),
            'couriers_url' => route('agencies.couriers.index', ['agency_id' => $id]),
            'contracts_url' => route('agencies.contracts.index', ['agency_id' => $id]),
            'documents_url' => route('agencies.documents.index', ['agency_id' => $id]),
            'activities_url' => route('agencies.activities.index', ['agency_id' => $id]),
        ], AgencyFeatures::earningsEnabled() ? [
            'monthly_earning_formatted' => $agency['monthly_earning_formatted'] ?? MoneyCalculator::format((float) $agency['monthly_earning']),
            'earnings_url' => route('agencies.earnings.index', ['agency_id' => $id]),
        ] : []);
  }

  /**
   * @return array<string, mixed>|null
   */
  public static function showPayload(int $id): ?array
  {
    $agency = self::find($id);

    if ($agency === null) {
      return null;
    }

    $slug = \Illuminate\Support\Str::slug(explode(' ', $agency['company_name'])[0] ?? 'acente');
    $paymentPeriods = AgencyFormData::paymentPeriods();
    $banks = AgencyFormData::banks();
    $defaultPaymentPeriod = ['weekly', 'monthly', 'biweekly'][$id % 3];
    $defaultBankKeys = ['ziraat', 'garanti', 'isbank', 'akbank'];

    return array_merge(self::detailPayload($agency), [
      'status' => $agency['status'],
      'uuid' => $agency['uuid'],
      'tax_number' => $agency['tax_number'],
      'tax_office' => $agency['tax_office'] ?? ($agency['city'].' Vergi Dairesi'),
      'brand_name' => $agency['brand_name'] ?? (explode(' ', $agency['company_name'])[0] ?? $agency['company_name']),
      'website' => $agency['website'] ?? ('https://www.'.$slug.'.com.tr'),
      'mersis_number' => $agency['mersis_number'] ?? (string) (3000000000000 + $id),
      'trade_registry_number' => $agency['trade_registry_number'] ?? ('TR-'.(120000 + $id)),
      'address' => $agency['address'] ?? ('Sanayi Mah. Lojistik Sk. No:'.($id * 4).' '.$agency['district'].' / '.$agency['city']),
      'commission_rate' => isset($agency['commission_rate'])
        ? (str_contains((string) $agency['commission_rate'], '%')
          ? $agency['commission_rate']
          : number_format((float) $agency['commission_rate'], 1, ',', '.').'%')
        : number_format(8 + ($id % 5), 1, ',', '.').'%',
      'payment_period_label' => $paymentPeriods[$agency['payment_period'] ?? $defaultPaymentPeriod] ?? 'Haftalık',
      'bank_name' => $banks[$agency['bank_key'] ?? $defaultBankKeys[$id % 4]] ?? 'Ziraat Bankası',
      'account_holder' => $agency['account_holder'] ?? $agency['authorized_person'],
      'iban' => $agency['iban'] ?? ('TR'.str_pad((string) (330000000000000000000000 + $id), 24, '0', STR_PAD_LEFT)),
      'notes' => $agency['notes'] ?? 'Acente profil kartı — sözleşme ve operasyon notları burada görüntülenir.',
      'created_at_formatted' => now()->subMonths(24 - min($id, 20))->format('d.m.Y'),
      'contacts' => AgencyContactDummyData::filter(['agency_id' => $id]),
      'couriers' => AgencyCourierDummyData::filter(['agency_id' => $id]),
      'contracts' => AgencyContractDummyData::filter(['agency_id' => $id]),
      'documents' => AgencyDocumentDummyData::filter(['agency_id' => $id]),
      'activities' => AgencyActivityDummyData::filter(['agency_id' => $id]),
    ], AgencyFeatures::earningsEnabled() ? [
      'monthly_earning' => $agency['monthly_earning_formatted'] ?? MoneyCalculator::format((float) $agency['monthly_earning']),
      'earnings' => AgencyEarningDummyData::filter(['agency_id' => $id]),
    ] : []);
  }

  /**
   * @return array<string, mixed>|null
   */
  public static function formPayload(int $id): ?array
  {
    $agency = self::find($id);

    if ($agency === null) {
      return null;
    }

    $slug = \Illuminate\Support\Str::slug(explode(' ', $agency['company_name'])[0] ?? 'acente');
    $bankKeys = ['ziraat', 'garanti', 'isbank', 'akbank'];
    $paymentPeriods = ['weekly', 'monthly', 'biweekly'];

    $formPayload = [
      'company_name' => $agency['company_name'],
      'brand_name' => explode(' ', $agency['company_name'])[0] ?? $agency['company_name'],
      'phone' => $agency['phone'],
      'email' => $agency['email'],
      'website' => 'https://www.'.$slug.'.com.tr',
      'tax_office' => $agency['city'].' Vergi Dairesi',
      'tax_number' => $agency['tax_number'],
      'mersis_number' => (string) (3000000000000 + $id),
      'trade_registry_number' => 'TR-'.(120000 + $id),
      'city' => $agency['city'],
      'district' => $agency['district'],
      'address' => 'Sanayi Mah. Lojistik Sk. No:'.($id * 4).' '.$agency['district'].' / '.$agency['city'],
      'commission_rate' => number_format(8 + ($id % 5), 1, ',', '.'),
      'payment_period' => $paymentPeriods[$id % 3],
      'bank_key' => $bankKeys[$id % 4],
      'account_holder' => $agency['authorized_person'],
      'iban' => 'TR'.str_pad((string) (330000000000000000000000 + $id), 24, '0', STR_PAD_LEFT),
      'status' => $agency['status'],
      'notes' => 'Acente profil kartı — sözleşme ve operasyon notları burada görüntülenir.',
      'logo_url' => $agency['logo_url'] ?? null,
    ];

    $stored = AgencyProfileStore::get($id);

    if ($stored !== []) {
      $formPayload = array_merge($formPayload, array_filter([
        'company_name' => $stored['company_name'] ?? null,
        'brand_name' => $stored['brand_name'] ?? null,
        'phone' => $stored['phone'] ?? null,
        'email' => $stored['email'] ?? null,
        'website' => $stored['website'] ?? null,
        'tax_office' => $stored['tax_office'] ?? null,
        'tax_number' => $stored['tax_number'] ?? null,
        'mersis_number' => $stored['mersis_number'] ?? null,
        'trade_registry_number' => $stored['trade_registry_number'] ?? null,
        'city' => $stored['city'] ?? null,
        'district' => $stored['district'] ?? null,
        'address' => $stored['address'] ?? null,
        'commission_rate' => $stored['commission_rate'] ?? null,
        'payment_period' => $stored['payment_period'] ?? null,
        'bank_key' => $stored['bank_key'] ?? null,
        'account_holder' => $stored['account_holder'] ?? null,
        'iban' => $stored['iban'] ?? null,
        'status' => $stored['status'] ?? null,
        'notes' => $stored['notes'] ?? null,
        'logo_url' => ! empty($stored['logo_path'])
          ? app(AgencyMediaService::class)->url($stored['logo_path'])
          : PublicMediaUrl::normalize($stored['logo_url'] ?? null),
      ], fn ($value) => $value !== null && $value !== ''));
    }

    return $formPayload;
  }
}
