<?php

namespace App\Modules\Courier\Data;

final class CourierEarningFormData
{
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
