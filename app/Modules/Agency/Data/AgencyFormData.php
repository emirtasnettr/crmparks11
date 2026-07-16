<?php

namespace App\Modules\Agency\Data;

use App\Modules\Business\Data\BusinessFormData;

class AgencyFormData
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function districtsByCity(): array
    {
        return BusinessFormData::districtsByCity();
    }

    /**
     * @return array<int, string>
     */
    public static function cities(): array
    {
        return BusinessFormData::cities();
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'pending' => 'Beklemede',
            'inactive' => 'Pasif',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function courierCountRanges(): array
    {
        return [
            '0' => '0 Kurye',
            '1-5' => '1 – 5 Kurye',
            '6-10' => '6 – 10 Kurye',
            '11-20' => '11 – 20 Kurye',
            '21+' => '21+ Kurye',
        ];
    }
}
