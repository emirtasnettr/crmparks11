<?php

namespace App\Modules\Stock\Data;

class StockFormData
{
    public const CRITICAL_STOCK_THRESHOLD = 10;

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Pasif',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function units(): array
    {
        return [
            'adet' => 'Adet',
            'çift' => 'Çift',
            'takım' => 'Takım',
            'koli' => 'Koli',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function assignmentStatuses(): array
    {
        return [
            'assigned' => 'Zimmette',
            'returned' => 'İade Edildi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function stockLevelLabels(): array
    {
        return [
            'out' => 'Stokta Yok',
            'critical' => 'Kritik Stok',
            'ok' => 'Yeterli',
        ];
    }
}
