<?php

namespace App\Modules\Business\Data;

class BusinessContactFormData
{
    /**
     * @return array<int, string>
     */
    public static function titles(): array
    {
        return [
            'İşletme Sahibi',
            'Şube Müdürü',
            'Operasyon Müdürü',
            'Restoran Müdürü',
            'Muhasebe Yetkilisi',
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
        ];
    }
}
