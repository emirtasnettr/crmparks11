<?php

namespace App\Modules\Agency\Data;

class AgencyContactFormData
{
    /**
     * @return array<int, string>
     */
    public static function titles(): array
    {
        return [
            'Firma Sahibi',
            'Operasyon Müdürü',
            'Finans Sorumlusu',
            'İnsan Kaynakları',
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
