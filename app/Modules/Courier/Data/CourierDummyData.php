<?php

namespace App\Modules\Courier\Data;

use App\Support\DemoData;
use App\Core\Profile\StoredProfileMerger;
use App\Modules\Business\Data\BusinessAssignmentDummyData;
use App\Modules\Courier\Services\CourierMediaService;
use App\Modules\Courier\Services\CourierProfileStore;
use App\Modules\Courier\Support\CourierFeatures;
use App\Support\PublicMediaUrl;

class CourierDummyData
{
    /**
     * @return array<string, string>
     */
    public static function vehicleTypes(): array
    {
        return [
            'motorcycle' => 'Motosiklet',
            'car' => 'Otomobil',
            'ebike' => 'Elektrikli Bisiklet',
            'bicycle' => 'Bisiklet',
            'pedestrian' => 'Yaya',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'on_leave' => 'İzinli',
            'inactive' => 'Pasif',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function courierTypes(): array
    {
        return [
            'independent' => 'Esnaf Kurye',
            'agency' => 'Acente Kuryesi',
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function agencies(): array
    {
        return BusinessAssignmentDummyData::agencies();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function raw(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return [
            ['id' => 1, 'first_name' => 'Ahmet', 'last_name' => 'Yıldız', 'phone' => '0532 100 10 01', 'tc_number' => '12345678901', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'Burger House Gıda Ltd. Şti.'],
            ['id' => 2, 'first_name' => 'Murat', 'last_name' => 'Kaya', 'phone' => '0533 100 10 02', 'tc_number' => '23456789012', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.'],
            ['id' => 3, 'first_name' => 'Emre', 'last_name' => 'Demir', 'phone' => '0534 100 10 03', 'tc_number' => '34567890123', 'courier_type' => 'agency', 'agency_id' => 1, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'Burger House Gıda Ltd. Şti.'],
            ['id' => 4, 'first_name' => 'Serkan', 'last_name' => 'Öz', 'phone' => '0535 100 10 04', 'tc_number' => '45678901234', 'courier_type' => 'agency', 'agency_id' => 2, 'vehicle_type' => 'car', 'status' => 'active', 'active_business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.'],
            ['id' => 5, 'first_name' => 'Volkan', 'last_name' => 'Arslan', 'phone' => '0536 100 10 05', 'tc_number' => '56789012345', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.'],
            ['id' => 6, 'first_name' => 'Burak', 'last_name' => 'Şen', 'phone' => '0537 100 10 06', 'tc_number' => '67890123456', 'courier_type' => 'agency', 'agency_id' => 1, 'vehicle_type' => 'ebike', 'status' => 'active', 'active_business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.'],
            ['id' => 7, 'first_name' => 'Cem', 'last_name' => 'Akın', 'phone' => '0538 100 10 07', 'tc_number' => '78901234567', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'motorcycle', 'status' => 'on_leave', 'active_business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.'],
            ['id' => 8, 'first_name' => 'Deniz', 'last_name' => 'Polat', 'phone' => '0539 100 10 08', 'tc_number' => '89012345678', 'courier_type' => 'agency', 'agency_id' => 3, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri'],
            ['id' => 9, 'first_name' => 'Efe', 'last_name' => 'Yalçın', 'phone' => '0541 100 10 09', 'tc_number' => '90123456789', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'ebike', 'status' => 'active', 'active_business_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.'],
            ['id' => 10, 'first_name' => 'Furkan', 'last_name' => 'Güneş', 'phone' => '0542 100 10 10', 'tc_number' => '11234567890', 'courier_type' => 'agency', 'agency_id' => 2, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.'],
            ['id' => 11, 'first_name' => 'Gökhan', 'last_name' => 'Tekin', 'phone' => '0543 200 20 11', 'tc_number' => '22345678901', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'car', 'status' => 'active', 'active_business_name' => 'Burger House Gıda Ltd. Şti.'],
            ['id' => 12, 'first_name' => 'Hakan', 'last_name' => 'Koç', 'phone' => '0544 200 20 12', 'tc_number' => '33456789012', 'courier_type' => 'agency', 'agency_id' => 1, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.'],
            ['id' => 13, 'first_name' => 'İbrahim', 'last_name' => 'Çetin', 'phone' => '0545 200 20 13', 'tc_number' => '44567890123', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'bicycle', 'status' => 'active', 'active_business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.'],
            ['id' => 14, 'first_name' => 'Kaan', 'last_name' => 'Aydın', 'phone' => '0546 200 20 14', 'tc_number' => '55678901234', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'motorcycle', 'status' => 'inactive', 'active_business_name' => null],
            ['id' => 15, 'first_name' => 'Levent', 'last_name' => 'Sarı', 'phone' => '0547 200 20 15', 'tc_number' => '66789012345', 'courier_type' => 'agency', 'agency_id' => 3, 'vehicle_type' => 'car', 'status' => 'active', 'active_business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.'],
            ['id' => 16, 'first_name' => 'Mert', 'last_name' => 'Korkmaz', 'phone' => '0548 200 20 16', 'tc_number' => '77890123456', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.'],
            ['id' => 17, 'first_name' => 'Oğuz', 'last_name' => 'Yılmaz', 'phone' => '0549 200 20 17', 'tc_number' => '88901234567', 'courier_type' => 'agency', 'agency_id' => 2, 'vehicle_type' => 'ebike', 'status' => 'on_leave', 'active_business_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri'],
            ['id' => 18, 'first_name' => 'Onur', 'last_name' => 'Başaran', 'phone' => '0551 200 20 18', 'tc_number' => '99012345678', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.'],
            ['id' => 19, 'first_name' => 'Rıza', 'last_name' => 'Öztürk', 'phone' => '0552 200 20 19', 'tc_number' => '10123456789', 'courier_type' => 'agency', 'agency_id' => 1, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'Burger House Gıda Ltd. Şti.'],
            ['id' => 20, 'first_name' => 'Selim', 'last_name' => 'Erdoğan', 'phone' => '0553 200 20 20', 'tc_number' => '21234567890', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'pedestrian', 'status' => 'active', 'active_business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.'],
            ['id' => 21, 'first_name' => 'Tolga', 'last_name' => 'Uçar', 'phone' => '0554 200 20 21', 'tc_number' => '32345678901', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'bicycle', 'status' => 'active', 'active_business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.'],
            ['id' => 22, 'first_name' => 'Umut', 'last_name' => 'Karaca', 'phone' => '0555 200 20 22', 'tc_number' => '43456789012', 'courier_type' => 'agency', 'agency_id' => 3, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.'],
            ['id' => 23, 'first_name' => 'Yasin', 'last_name' => 'Duman', 'phone' => '0556 200 20 23', 'tc_number' => '54567890123', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'car', 'status' => 'inactive', 'active_business_name' => null],
            ['id' => 24, 'first_name' => 'Zafer', 'last_name' => 'Işık', 'phone' => '0557 200 20 24', 'tc_number' => '65678901234', 'courier_type' => 'agency', 'agency_id' => 2, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.'],
            ['id' => 25, 'first_name' => 'Barış', 'last_name' => 'Tunç', 'phone' => '0558 200 20 25', 'tc_number' => '76789012345', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'ebike', 'status' => 'active', 'active_business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.'],
            ['id' => 26, 'first_name' => 'Caner', 'last_name' => 'Bilgin', 'phone' => '0559 200 20 26', 'tc_number' => '87890123456', 'courier_type' => 'agency', 'agency_id' => 1, 'vehicle_type' => 'motorcycle', 'status' => 'on_leave', 'active_business_name' => 'Burger House Gıda Ltd. Şti.'],
            ['id' => 27, 'first_name' => 'Doğan', 'last_name' => 'Sezer', 'phone' => '0561 200 20 27', 'tc_number' => '98901234567', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'motorcycle', 'status' => 'active', 'active_business_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri'],
            ['id' => 28, 'first_name' => 'Erhan', 'last_name' => 'Vural', 'phone' => '0562 200 20 28', 'tc_number' => '19012345678', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'bicycle', 'status' => 'active', 'active_business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.'],
            ['id' => 29, 'first_name' => 'Fatih', 'last_name' => 'Gencer', 'phone' => '0563 200 20 29', 'tc_number' => '29123456789', 'courier_type' => 'agency', 'agency_id' => 3, 'vehicle_type' => 'car', 'status' => 'active', 'active_business_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.'],
            ['id' => 30, 'first_name' => 'Halil', 'last_name' => 'Özkan', 'phone' => '0564 200 20 30', 'tc_number' => '39234567890', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'pedestrian', 'status' => 'inactive', 'active_business_name' => null],
            ['id' => 31, 'first_name' => 'İlker', 'last_name' => 'Mutlu', 'phone' => '0565 200 20 31', 'tc_number' => '49345678901', 'courier_type' => 'agency', 'agency_id' => 2, 'vehicle_type' => 'ebike', 'status' => 'active', 'active_business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.'],
            ['id' => 32, 'first_name' => 'Koray', 'last_name' => 'Aslan', 'phone' => '0566 200 20 32', 'tc_number' => '59456789012', 'courier_type' => 'independent', 'agency_id' => null, 'vehicle_type' => 'motorcycle', 'status' => 'on_leave', 'active_business_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.'],
        ];
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
            ->map(fn (array $courier) => self::enrich(self::mergeStoredProfile((int) $courier['id'], $courier)))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $courier
     * @return array<string, mixed>
     */
    public static function enrich(array $courier): array
    {
        $agency = $courier['agency_id']
            ? collect(self::agencies())->firstWhere('id', $courier['agency_id'])
            : null;

        $avatarColors = [
            'bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-amber-500',
            'bg-rose-500', 'bg-cyan-500', 'bg-indigo-500', 'bg-orange-500',
        ];

        return array_merge($courier, [
            'uuid' => 'crr-'.str_pad((string) $courier['id'], 3, '0', STR_PAD_LEFT),
            'full_name' => $courier['first_name'].' '.$courier['last_name'],
            'agency_name' => $agency['name'] ?? null,
            'vehicle_type_label' => self::vehicleTypes()[$courier['vehicle_type']] ?? '—',
            'courier_type_label' => self::courierTypes()[$courier['courier_type']] ?? '—',
            'avatar_initials' => mb_strtoupper(mb_substr($courier['first_name'], 0, 1).mb_substr($courier['last_name'], 0, 1)),
            'avatar_color' => $avatarColors[($courier['id'] - 1) % count($avatarColors)],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public static function summary(array $filters): array
    {
        $items = self::filter($filters);

        return [
            'total' => count($items),
            'active' => collect($items)->where('status', 'active')->count(),
            'independent' => collect($items)->where('courier_type', 'independent')->count(),
            'agency' => collect($items)->where('courier_type', 'agency')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        return collect(self::all())
            ->filter(function (array $courier) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $courier['full_name'],
                        $courier['phone'],
                        $courier['tc_number'],
                    ]));

                    if (! str_contains($haystack, $search)) {
                        return false;
                    }
                }

                if (! empty($filters['courier_type']) && $filters['courier_type'] !== 'all') {
                    if ($courier['courier_type'] !== $filters['courier_type']) {
                        return false;
                    }
                }

                if (! empty($filters['agency_id']) && $filters['agency_id'] !== 'all') {
                    if ((int) ($courier['agency_id'] ?? 0) !== (int) $filters['agency_id']) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($courier['status'] !== $filters['status']) {
                        return false;
                    }
                }

                if (! empty($filters['vehicle_type']) && $filters['vehicle_type'] !== 'all') {
                    if ($courier['vehicle_type'] !== $filters['vehicle_type']) {
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
        return collect(self::raw())->contains(fn (array $courier) => (int) $courier['id'] === $id);
    }

    /**
     * @param  array<string, mixed>  $courier
     * @return array<string, mixed>
     */
    public static function mergeStoredProfile(int $id, array $courier): array
    {
        $stored = CourierProfileStore::get($id);

        if ($stored === []) {
            return $courier;
        }

        $courier = StoredProfileMerger::apply($courier, $stored, [
            'first_name',
            'last_name',
            'tc_number',
            'birth_date',
            'phone',
            'email',
            'courier_type',
            'tax_office',
            'tax_number',
            'company_name',
            'city',
            'district',
            'address',
            'vehicle_type',
            'plate',
            'vehicle_brand',
            'vehicle_model',
            'bank_name',
            'iban',
            'account_holder',
            'start_date',
            'status',
            'notes',
        ]);

        if (array_key_exists('agency_id', $stored)) {
            $courier['agency_id'] = $stored['agency_id'] !== '' && $stored['agency_id'] !== null
                ? (int) $stored['agency_id']
                : null;
        }

        if (! empty($stored['photo_path']) || ! empty($stored['photo_url'])) {
            $media = app(CourierMediaService::class);
            $courier['photo_path'] = $stored['photo_path'] ?? null;
            $courier['photo_url'] = ! empty($stored['photo_path'])
                ? $media->url($stored['photo_path'])
                : PublicMediaUrl::normalize($stored['photo_url'] ?? null);
            $courier['has_profile_photo'] = ! empty($courier['photo_url']);
        }

        return $courier;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $courier = collect(self::raw())->firstWhere('id', $id);

        if ($courier === null) {
            return null;
        }

        return self::enrich(self::mergeStoredProfile($id, $courier));
    }

    /**
     * @param  array<string, mixed>  $courier
     * @return array<string, mixed>
     */
    public static function detailPayload(array $courier): array
    {
        $id = (int) $courier['id'];
        $enriched = isset($courier['full_name']) ? $courier : self::enrich($courier);

        return array_merge([
            'id' => $id,
            'avatar_initials' => $enriched['avatar_initials'],
            'avatar_color' => $enriched['avatar_color'],
            'photo_url' => $enriched['photo_url'] ?? null,
            'has_profile_photo' => (bool) ($enriched['has_profile_photo'] ?? false),
            'full_name' => $enriched['full_name'],
            'agency_name' => $enriched['agency_name'],
            'phone' => $enriched['phone'],
            'courier_type_label' => $enriched['courier_type_label'],
            'vehicle_type_label' => $enriched['vehicle_type_label'],
            'active_business_name' => $enriched['active_business_name'],
            'status_label' => self::statuses()[$enriched['status']] ?? $enriched['status'],
            'work_history_url' => route('couriers.work-history.index', ['courier_id' => $id]),
            'documents_url' => route('couriers.documents.index', ['courier_id' => $id]),
            'bank_accounts_url' => route('couriers.bank-accounts.index', ['courier_id' => $id]),
        ], CourierFeatures::earningsEnabled() ? [
            'earnings_url' => route('couriers.earnings.index', ['courier_id' => $id]),
        ] : []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function showPayload(int $id): ?array
    {
        $courier = self::find($id);

        if ($courier === null) {
            return null;
        }

        $vehicles = CourierVehicleDummyData::filter(['courier_id' => $id]);
        $activeVehicle = collect($vehicles)->firstWhere('status', 'active') ?? $vehicles[0] ?? null;
        $defaultBank = collect(CourierBankAccountDummyData::filter(['courier_id' => $id]))
            ->firstWhere('is_default', true)
            ?? CourierBankAccountDummyData::filter(['courier_id' => $id])[0]
            ?? null;

        $defaultEmail = \Illuminate\Support\Str::slug($courier['first_name']).'.'.\Illuminate\Support\Str::slug($courier['last_name']).'@ornek.com';
        $defaultCities = ['İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya'];
        $defaultDistricts = ['Kadıköy', 'Çankaya', 'Bornova', 'Nilüfer', 'Muratpaşa'];

        return array_merge(self::detailPayload($courier), [
            'status' => $courier['status'],
            'uuid' => $courier['uuid'],
            'tc_number' => $courier['tc_number'],
            'email' => $courier['email'] ?? $defaultEmail,
            'birth_date_formatted' => ! empty($courier['birth_date'])
                ? \Carbon\Carbon::parse($courier['birth_date'])->format('d.m.Y')
                : now()->subYears(28 + ($id % 10))->format('d.m.Y'),
            'city' => $courier['city'] ?? $defaultCities[$id % 5],
            'district' => $courier['district'] ?? $defaultDistricts[$id % 5],
            'address' => $courier['address'] ?? ('Örnek Mah. No:'.($id * 2).' — teslimat bölgesi kayıtlı adres'),
            'tax_office' => $courier['courier_type'] === 'independent'
                ? ($courier['tax_office'] ?? 'Esnaf Vergi Dairesi')
                : null,
            'tax_number' => $courier['courier_type'] === 'independent'
                ? ($courier['tax_number'] ?? (string) (2000000000 + $id))
                : null,
            'company_name' => $courier['courier_type'] === 'independent'
                ? ($courier['company_name'] ?? $courier['full_name'])
                : null,
            'start_date_formatted' => ! empty($courier['start_date'])
                ? \Carbon\Carbon::parse($courier['start_date'])->format('d.m.Y')
                : now()->subMonths(6 + ($id % 18))->format('d.m.Y'),
            'notes' => $courier['notes'] ?? 'Kurye profil kartı — operasyon ve evrak notları burada listelenir.',
            'active_vehicle' => $activeVehicle,
            'default_bank' => $defaultBank,
            'vehicles' => $vehicles,
            'bank_accounts' => CourierBankAccountDummyData::filter(['courier_id' => $id]),
            'documents' => CourierDocumentDummyData::filter(['courier_id' => $id]),
            'work_history' => CourierWorkHistoryDummyData::filter(['courier_id' => $id]),
            'activities' => CourierActivityDummyData::filter(['courier_id' => $id]),
            'vehicles_url' => route('couriers.vehicles.index', ['courier_id' => $id]),
            'activities_url' => route('couriers.activities.index', ['courier_id' => $id]),
        ], CourierFeatures::earningsEnabled() ? [
            'earnings' => CourierEarningDummyData::filter(['courier_id' => $id]),
        ] : []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function formPayload(int $id): ?array
    {
        $courier = self::find($id);

        if ($courier === null) {
            return null;
        }

        $payload = self::showPayload($id);
        $vehicle = $payload['active_vehicle'] ?? null;
        $bank = $payload['default_bank'] ?? null;

        $formPayload = [
            'first_name' => $courier['first_name'],
            'last_name' => $courier['last_name'],
            'tc_number' => $courier['tc_number'],
            'birth_date' => now()->subYears(28 + ($id % 10))->format('Y-m-d'),
            'phone' => $courier['phone'],
            'email' => $payload['email'],
            'courier_type' => $courier['courier_type'],
            'agency_id' => $courier['agency_id'] ? (string) $courier['agency_id'] : '',
            'tax_office' => $payload['tax_office'] ?? '',
            'tax_number' => $payload['tax_number'] ?? '',
            'company_name' => $payload['company_name'] ?? '',
            'city' => $payload['city'],
            'district' => $payload['district'],
            'address' => $payload['address'],
            'vehicle_type' => $courier['vehicle_type'],
            'plate' => $vehicle['plate'] ?? '',
            'vehicle_brand' => $vehicle['brand'] ?? '',
            'vehicle_model' => $vehicle['model'] ?? '',
            'bank_name' => $bank['bank_key'] ?? '',
            'iban' => $bank['iban_formatted'] ?? ($bank['iban'] ?? ''),
            'account_holder' => $bank['account_holder'] ?? '',
            'start_date' => now()->subMonths(6 + ($id % 18))->format('Y-m-d'),
            'status' => $courier['status'],
            'notes' => $payload['notes'],
            'photo_url' => $courier['photo_url'] ?? null,
        ];

        $stored = CourierProfileStore::get($id);

        if ($stored !== []) {
            $formPayload = array_merge($formPayload, array_filter([
                'first_name' => $stored['first_name'] ?? null,
                'last_name' => $stored['last_name'] ?? null,
                'tc_number' => $stored['tc_number'] ?? null,
                'birth_date' => $stored['birth_date'] ?? null,
                'phone' => $stored['phone'] ?? null,
                'email' => $stored['email'] ?? null,
                'courier_type' => $stored['courier_type'] ?? null,
                'agency_id' => $stored['agency_id'] ?? null,
                'tax_office' => $stored['tax_office'] ?? null,
                'tax_number' => $stored['tax_number'] ?? null,
                'company_name' => $stored['company_name'] ?? null,
                'city' => $stored['city'] ?? null,
                'district' => $stored['district'] ?? null,
                'address' => $stored['address'] ?? null,
                'vehicle_type' => $stored['vehicle_type'] ?? null,
                'plate' => $stored['plate'] ?? null,
                'vehicle_brand' => $stored['vehicle_brand'] ?? null,
                'vehicle_model' => $stored['vehicle_model'] ?? null,
                'bank_name' => $stored['bank_name'] ?? null,
                'iban' => $stored['iban'] ?? null,
                'account_holder' => $stored['account_holder'] ?? null,
                'start_date' => $stored['start_date'] ?? null,
                'status' => $stored['status'] ?? null,
                'notes' => $stored['notes'] ?? null,
                'photo_url' => ! empty($stored['photo_path'])
                    ? app(CourierMediaService::class)->url($stored['photo_path'])
                    : PublicMediaUrl::normalize($stored['photo_url'] ?? null),
            ], fn ($value) => $value !== null && $value !== ''));
        }

        return $formPayload;
    }
}
