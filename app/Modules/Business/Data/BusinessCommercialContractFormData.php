<?php

namespace App\Modules\Business\Data;

class BusinessCommercialContractFormData
{
    /**
     * @return array<string, string>
     */
    public static function workTypes(): array
    {
        return [
            'per_package' => 'Paket Başı',
            'hourly' => 'Saatlik Ücret',
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
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'ended' => 'Sona Erdi',
        ];
    }
}
