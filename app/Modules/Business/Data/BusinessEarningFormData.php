<?php

namespace App\Modules\Business\Data;

final class BusinessEarningFormData
{
    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'draft' => 'Taslak',
            'pending' => 'Bekliyor',
            'approved' => 'Onaylandı',
            'paid' => 'Ödendi',
            'cancelled' => 'İptal',
        ];
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
     * @return array<int, string>
     */
    public static function months(): array
    {
        return [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];
    }
}
