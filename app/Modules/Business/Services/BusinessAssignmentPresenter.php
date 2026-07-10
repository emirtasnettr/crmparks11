<?php

namespace App\Modules\Business\Services;

use App\Modules\Business\Models\BusinessCourierAssignment;
use Carbon\Carbon;

class BusinessAssignmentPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(BusinessCourierAssignment $assignment): array
    {
        return $this->enrich($assignment);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(BusinessCourierAssignment $assignment): array
    {
        return $this->enrich($assignment);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(BusinessCourierAssignment $assignment): array
    {
        $assignment->loadMissing(['business', 'courier.agency']);

        $courier = $assignment->courier;
        $business = $assignment->business;
        $agency = $courier?->agency;
        $courierType = $courier?->courier_type ?? 'independent';
        $today = Carbon::today();
        $endDate = $assignment->end_date;
        $workStatus = $this->resolveWorkStatus($assignment->status, $endDate, $today);

        return [
            'id' => $assignment->id,
            'courier_id' => $assignment->courier_id,
            'business_id' => $assignment->business_id,
            'agency_id' => $courier?->agency_id,
            'courier_type' => $courierType,
            'courier_name' => $courier?->full_name ?? '—',
            'courier_phone' => $courier?->phone ?? '—',
            'business_name' => $business?->displayName() ?? '—',
            'business_brand' => $business?->brand_name ?? '—',
            'agency_name' => $agency?->displayName() ?? '—',
            'courier_type_label' => $courierType === 'agency'
                ? ($agency?->company_name ?: '—')
                : 'Esnaf Kurye',
            'status' => $assignment->status,
            'work_status' => $workStatus,
            'start_date' => $assignment->start_date?->toDateString(),
            'end_date' => $endDate?->toDateString(),
            'start_date_formatted' => $assignment->start_date?->format('d.m.Y') ?? '—',
            'end_date_formatted' => $endDate?->format('d.m.Y') ?? '—',
            'notes' => $assignment->notes,
            'is_active_assignment' => $assignment->status === 'active' && $workStatus !== 'left',
        ];
    }

    private function resolveWorkStatus(string $status, ?Carbon $endDate, Carbon $today): string
    {
        if ($status === 'inactive' || ($endDate && $endDate->lt($today))) {
            return 'left';
        }

        if ($endDate && $endDate->gte($today) && $today->diffInDays($endDate, false) <= 14) {
            return 'leaving_soon';
        }

        return 'active';
    }
}
