<?php

namespace App\Modules\Finance\Data;

class InvoiceFormData
{
    /**
     * @return array<string, string>
     */
    public static function invoiceTypes(): array
    {
        return [
            'e_invoice' => 'e-Fatura',
            'e_archive' => 'e-Arşiv',
            'manual' => 'Manuel',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function invoiceStatuses(): array
    {
        return [
            'issued' => 'Kesildi',
            'draft' => 'Taslak',
            'cancelled' => 'İptal',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function collectionStatuses(): array
    {
        return [
            'collected' => 'Tahsil Edildi',
            'partial' => 'Kısmi Tahsil',
            'pending' => 'Bekliyor',
            'overdue' => 'Vadesi Geçti',
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
    public static function sources(): array
    {
        return [
            'earning' => 'Hakediş',
            'manual' => 'Manuel',
        ];
    }
}
