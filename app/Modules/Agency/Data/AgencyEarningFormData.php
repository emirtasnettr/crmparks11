<?php

namespace App\Modules\Agency\Data;

final class AgencyEarningFormData
{
    /**
     * @return array<string, string>
     */
    public static function earningStatuses(): array
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
    public static function paymentStatuses(): array
    {
        return [
            'pending' => 'Bekliyor',
            'paid' => 'Ödendi',
            'partial' => 'Kısmi Ödendi',
            'cancelled' => 'İptal',
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
