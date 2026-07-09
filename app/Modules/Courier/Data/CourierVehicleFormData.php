<?php

namespace App\Modules\Courier\Data;

final class CourierVehicleFormData
{
    /**
     * @return array<string, string>
     */
    public static function vehicleTypes(): array
    {
        return CourierFormData::vehicleTypes();
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
