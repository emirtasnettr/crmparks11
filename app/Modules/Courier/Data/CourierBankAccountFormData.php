<?php

namespace App\Modules\Courier\Data;

final class CourierBankAccountFormData
{
    /**
     * @return array<string, string>
     */
    public static function banks(): array
    {
        return CourierFormData::banks();
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

    /**
     * @return array<string, string>
     */
    public static function defaultFilters(): array
    {
        return [
            'yes' => 'Varsayılan',
            'no' => 'Varsayılan Değil',
        ];
    }
}
