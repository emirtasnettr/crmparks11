<?php

namespace App\Modules\Finance\Data;

class RevenueFormData
{
    /**
     * @return array<string, string>
     */
    public static function revenueTypes(): array
    {
        return [
            'per_package' => 'Paket Başı Hizmet',
            'fixed_monthly' => 'Aylık Sabit Hizmet',
            'extra_service' => 'Ek Hizmet',
            'penalty' => 'Ceza Bedeli',
            'manual' => 'Manuel Gelir',
            'other' => 'Diğer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function collectionStatuses(): array
    {
        return [
            'collected' => 'Tahsil Edildi',
            'pending' => 'Bekliyor',
            'overdue' => 'Gecikmiş',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function invoiceStatuses(): array
    {
        return [
            'issued' => 'Kesildi',
            'pending' => 'Bekliyor',
            'none' => 'Fatura Yok',
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
}
