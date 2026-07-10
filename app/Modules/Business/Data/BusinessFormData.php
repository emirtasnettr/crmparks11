<?php

namespace App\Modules\Business\Data;

class BusinessFormData
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function districtsByCity(): array
    {
        return [
            'İstanbul' => ['Kadıköy', 'Beşiktaş', 'Şişli', 'Ümraniye', 'Ataşehir', 'Bakırköy', 'Fatih', 'Maltepe'],
            'Ankara' => ['Çankaya', 'Keçiören', 'Yenimahalle', 'Mamak', 'Etimesgut'],
            'İzmir' => ['Konak', 'Karşıyaka', 'Bornova', 'Buca', 'Bayraklı'],
            'Bursa' => ['Osmangazi', 'Nilüfer', 'Yıldırım', 'Gemlik'],
            'Antalya' => ['Muratpaşa', 'Kepez', 'Konyaaltı', 'Alanya'],
            'Adana' => ['Seyhan', 'Çukurova', 'Yüreğir', 'Sarıçam'],
            'Konya' => ['Selçuklu', 'Meram', 'Karatay'],
            'Gaziantep' => ['Şahinbey', 'Şehitkamil', 'Oğuzeli'],
        ];
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