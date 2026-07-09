<?php

namespace App\Modules\Courier\Services;

use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Data\CourierFormData;
use Carbon\Carbon;

class CourierWorkHistoryPresenter
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

    public function workStatus(BusinessCourierAssignment $assignment): string
    {
        return $this->resolveWorkStatus(
            $assignment->status,
            $assignment->end_date,
            Carbon::today(),
        );
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
        $today = Carbon::today();
        $startDate = $assignment->start_date;
        $endDate = $assignment->end_date;
        $workStatus = $this->resolveWorkStatus($assignment->status, $endDate, $today);
        $durationEnd = ($workStatus === 'completed' && $endDate) ? $endDate : $today;

        return [
            'id' => $assignment->id,
            'uuid' => $assignment->uuid,
            'courier_id' => $assignment->courier_id,
            'business_id' => $assignment->business_id,
            'agency_id' => $courier?->agency_id,
            'courier_type' => $courier?->courier_type ?? 'independent',
            'courier_name' => $courier?->full_name ?? '—',
            'courier_phone' => $courier?->phone ?? '—',
            'business_name' => $business?->company_name ?? '—',
            'business_brand' => $business?->brand_name ?? '—',
            'agency_name' => $agency?->company_name ?? '—',
            'courier_type_label' => CourierFormData::courierTypes()[$courier?->courier_type ?? 'independent'] ?? '—',
            'status' => $assignment->status,
            'work_status' => $workStatus,
            'work_status_label' => self::workStatusLabels()[$workStatus] ?? '—',
            'start_date' => $startDate?->toDateString(),
            'end_date' => $endDate?->toDateString(),
            'start_date_formatted' => $startDate?->format('d.m.Y') ?? '—',
            'end_date_formatted' => $endDate?->format('d.m.Y') ?? '—',
            'work_duration' => $startDate ? $this->formatDuration($startDate, $durationEnd) : '—',
            'work_duration_days' => $startDate ? $startDate->diffInDays($durationEnd) + 1 : 0,
            'notes' => $assignment->notes,
            'is_ongoing' => in_array($workStatus, ['active', 'leaving_soon'], true),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function workStatusLabels(): array
    {
        return [
            'active' => 'Aktif',
            'completed' => 'Tamamlandı',
            'leaving_soon' => 'Yakında Ayrılıyor',
        ];
    }

    private function resolveWorkStatus(string $status, ?Carbon $endDate, Carbon $today): string
    {
        if ($status === 'completed' || ($endDate && $endDate->lt($today))) {
            return 'completed';
        }

        if ($endDate && $endDate->gte($today) && $today->diffInDays($endDate, false) <= 14) {
            return 'leaving_soon';
        }

        return 'active';
    }

    private function formatDuration(Carbon $start, Carbon $end): string
    {
        $days = $start->diffInDays($end) + 1;

        if ($days < 30) {
            return $days.' gün';
        }

        $months = (int) $start->diffInMonths($end);
        $monthAnchor = $start->copy()->addMonths($months);
        $remainingDays = $monthAnchor->lte($end) ? $monthAnchor->diffInDays($end) : 0;

        if ($months < 12) {
            return $remainingDays > 0 ? "{$months} ay {$remainingDays} gün" : "{$months} ay";
        }

        $years = (int) $start->diffInYears($end);
        $yearAnchor = $start->copy()->addYears($years);
        $remainingMonths = $yearAnchor->lte($end) ? (int) $yearAnchor->diffInMonths($end) : 0;

        return $remainingMonths > 0 ? "{$years} yıl {$remainingMonths} ay" : "{$years} yıl";
    }
}
