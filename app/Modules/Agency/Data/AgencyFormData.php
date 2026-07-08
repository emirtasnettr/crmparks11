<?php

namespace App\Modules\Agency\Data;

use App\Modules\Business\Data\BusinessFormData;
use App\Modules\Courier\Data\CourierFormData;

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
    public static function paymentPeriods(): array
    {
        return [
            'weekly' => 'Haftalık',
            'biweekly' => '15 Günlük',
            'monthly' => 'Aylık',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function banks(): array
    {
        return CourierFormData::banks();
    }
}
