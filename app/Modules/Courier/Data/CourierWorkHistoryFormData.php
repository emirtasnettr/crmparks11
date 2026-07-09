<?php

namespace App\Modules\Courier\Data;

final class CourierWorkHistoryFormData
{
    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'completed' => 'Tamamlandı',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function courierTypes(): array
    {
        return CourierFormData::courierTypes();
    }

    /**
     * @return array<string, string>
     */
    public static function dateRanges(): array
    {
        return [
            'last_7_days' => 'Son 7 Gün',
            'last_30_days' => 'Son 30 Gün',
            'this_month' => 'Bu Ay',
            'last_3_months' => 'Son 3 Ay',
            'this_year' => 'Bu Yıl',
        ];
    }
}
