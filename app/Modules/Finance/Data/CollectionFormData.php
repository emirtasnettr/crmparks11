<?php

namespace App\Modules\Finance\Data;

class CollectionFormData
{
    /**
     * @return array<string, string>
     */
    public static function collectionStatuses(): array
    {
        return [
            'collected' => 'Tahsil Edildi',
            'partial' => 'Kısmi Tahsil Edildi',
            'pending' => 'Bekliyor',
            'overdue' => 'Vadesi Geçti',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paymentMethods(): array
    {
        return [
            'bank_transfer' => 'Banka Havalesi',
            'eft' => 'EFT',
            'fast' => 'FAST',
            'cash' => 'Nakit',
            'credit_card' => 'Kredi Kartı',
            'offset' => 'Mahsup',
            'other' => 'Diğer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dateRanges(): array
    {
        return [
            'all' => 'Tümü',
            'today' => 'Bugün',
            'week' => 'Bu Hafta',
            'month' => 'Bu Ay',
            'year' => 'Bu Yıl',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dueDateFilters(): array
    {
        return [
            'all' => 'Tümü',
            'overdue' => 'Vadesi Geçen',
            'today' => 'Bugün Vadeli',
            'week' => 'Bu Hafta Vadeli',
            'month' => 'Bu Ay Vadeli',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sources(): array
    {
        return [
            'revenue' => 'Gelir',
            'earning' => 'Hakediş',
            'manual' => 'Manuel',
        ];
    }
}
