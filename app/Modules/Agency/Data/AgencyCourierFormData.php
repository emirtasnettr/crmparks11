<?php

namespace App\Modules\Agency\Data;

use App\Models\VehicleType;

class AgencyCourierFormData
{
    /**
     * @return array<string, string>
     */
    public static function vehicleTypes(): array
    {
        return VehicleType::query()
            ->orderBy('sort_order')
            ->pluck('label', 'code')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'on_leave' => 'İzinli',
            'inactive' => 'Pasif',
        ];
    }
}
