<?php

namespace App\Modules\Agency\Services;

use App\Modules\Agency\Data\AgencyCourierFormData;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use Carbon\Carbon;

class AgencyCourierPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(Courier $courier, ?BusinessCourierAssignment $assignment = null): array
    {
        $courier->loadMissing(['agency', 'vehicleType']);

        $vehicleTypes = AgencyCourierFormData::vehicleTypes();
        $vehicleCode = $courier->vehicleType?->code ?? 'motor';
        $joinDate = $courier->start_date;
        $initials = $this->initials($courier->full_name);
        $activeBusiness = $assignment?->business?->displayName();

        return [
            'id' => $courier->id,
            'courier_id' => $courier->id,
            'agency_id' => $courier->agency_id,
            'agency_name' => $courier->agency?->displayName() ?? '—',
            'courier_name' => $courier->full_name,
            'phone' => $courier->phone ?? '—',
            'vehicle_type' => $vehicleCode,
            'vehicle_type_label' => $vehicleTypes[$vehicleCode] ?? ($courier->vehicleType?->label ?? '—'),
            'active_business_name' => $activeBusiness,
            'join_date' => $joinDate?->toDateString(),
            'join_date_formatted' => $joinDate?->format('d.m.Y') ?? '—',
            'status' => $courier->status ?? 'active',
            'is_current' => true,
            'avatar_initials' => $initials,
            'avatar_color' => $this->avatarColor($courier->id),
            'courier_type' => $courier->courier_type ?? 'agency',
            'courier_type_label' => 'Acente Kuryesi',
            'vehicle_plate' => $courier->plate ?: '—',
            'vehicle_info' => trim(($vehicleTypes[$vehicleCode] ?? '—').($courier->plate ? ' · '.$courier->plate : '')),
            'notes' => $courier->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(Courier $courier, ?BusinessCourierAssignment $assignment = null): array
    {
        return $this->indexRow($courier, $assignment);
    }

    private function initials(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) === 0) {
            return '—';
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
    }

    private function avatarColor(int $courierId): string
    {
        $colors = [
            'bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-amber-500',
            'bg-rose-500', 'bg-cyan-500', 'bg-indigo-500', 'bg-orange-500',
        ];

        return $colors[($courierId - 1) % count($colors)];
    }
}
