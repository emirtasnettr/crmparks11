<?php

namespace App\Modules\Business\Data;

use App\Models\City;
use Illuminate\Support\Facades\Schema;

class BusinessFormData
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function districtsByCity(): array
    {
        if (Schema::hasTable('cities') && Schema::hasTable('districts')) {
            $fromDb = City::query()
                ->with(['districts' => fn ($query) => $query->orderBy('name')])
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (City $city) => [
                    $city->name => $city->districts->pluck('name')->values()->all(),
                ])
                ->filter(fn (array $districts) => $districts !== [])
                ->all();

            if ($fromDb !== []) {
                return $fromDb;
            }
        }

        return self::fallbackDistrictsByCity();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function fallbackDistrictsByCity(): array
    {
        $path = database_path('data/turkey_cities_districts.json');

        if (! is_file($path)) {
            return [];
        }

        try {
            /** @var array<int, array{name: string, districts?: array<int, string>}> $cities */
            $cities = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return collect($cities)
            ->mapWithKeys(fn (array $city) => [
                $city['name'] => array_values($city['districts'] ?? []),
            ])
            ->filter(fn (array $districts) => $districts !== [])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function cities(): array
    {
        return array_keys(self::districtsByCity());
    }

    /**
     * @return array<string, string>
     */
    public static function pricingModels(): array
    {
        return [
            'per_package' => 'Paket Başı',
            'monthly_fixed' => 'Aylık Sabit',
            'hourly' => 'Saatlik',
            'daily' => 'Günlük',
        ];
    }

    /**
     * @return array<string, array{customer: string, courier: string}>
     */
    public static function pricingFieldLabels(): array
    {
        return [
            'per_package' => [
                'customer' => 'İşletmeden Alınacak Paket Ücreti (₺)',
                'courier' => 'Kuryeye Ödenecek Paket Ücreti (₺)',
            ],
            'monthly_fixed' => [
                'customer' => 'İşletmeden Aylık Alınacak Tutar (₺)',
                'courier' => 'Kuryeye Aylık Ödenecek Tutar (₺)',
            ],
            'hourly' => [
                'customer' => 'İşletmeden Saatlik Ücret (₺)',
                'courier' => 'Kuryeye Saatlik Ücret (₺)',
            ],
            'daily' => [
                'customer' => 'İşletmeden Günlük Ücret (₺)',
                'courier' => 'Kuryeye Günlük Ücret (₺)',
            ],
        ];
    }

    /**
     * İşletme detay özet kartları için kontrat çalışma tipine göre kısa etiketler.
     *
     * @return array{received: string, courier: string, net: string, subtitle: string, customer_detail: string, courier_detail: string}
     */
    public static function overviewPricingLabels(?string $workType): array
    {
        return match ($workType) {
            'hourly' => [
                'received' => 'Saatlik Alınan',
                'courier' => 'Saatlik Kuryeye Verilen',
                'net' => 'Saatlik Net Kazanç',
                'subtitle' => 'saatlik göstergeler',
                'customer_detail' => 'İşletmeden Saatlik Ücret',
                'courier_detail' => 'Kuryeye Saatlik Ücret',
            ],
            default => [
                'received' => 'Paket Başı Alınan',
                'courier' => 'Paket Başı Kuryeye Verilen',
                'net' => 'Paket Başı Net Kazanç',
                'subtitle' => 'paket bazlı göstergeler',
                'customer_detail' => 'İşletmeden Alınacak Paket Ücreti',
                'courier_detail' => 'Kuryeye Ödenecek Paket Ücreti',
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function earningPeriods(): array
    {
        return [
            'weekly' => 'Haftalık',
            'biweekly' => '15 Günlük',
            'monthly' => 'Aylık',
        ];
    }

    public static function defaultFirstInvoiceDate(): string
    {
        return now()->addMonthNoOverflow()->startOfMonth()->toDateString();
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Pasif',
            'pending' => 'Beklemede',
            'contract_stage' => 'Sözleşme Aşamasında',
            'opening_stage' => 'Açılış Aşamasında',
        ];
    }
}
