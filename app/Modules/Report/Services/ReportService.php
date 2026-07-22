<?php

namespace App\Modules\Report\Services;

use App\Modules\Business\Models\Business;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Support\ShiftAttendanceRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return array{
     *     work_date: string,
     *     work_date_formatted: string,
     *     rows: array<int, array<string, mixed>>,
     *     summary: array{businesses: int, shifts: int, required: int, assigned: int, missing: int}
     * }
     */
    public function radar(?Carbon $day = null): array
    {
        $day ??= Carbon::today();
        $now = now();
        $date = $day->toDateString();
        $isFuture = ShiftAttendanceRules::isFutureDay($day, $now);

        $todayShifts = BusinessShift::query()
            ->with(['rosterCouriers:id,full_name,phone'])
            ->where('is_active', true)
            ->get()
            ->filter(fn (BusinessShift $shift) => $shift->runsOn($day))
            ->sortBy(fn (BusinessShift $shift) => (string) $shift->start_time)
            ->values();

        $businessIdsWithShiftToday = $todayShifts
            ->pluck('business_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($businessIdsWithShiftToday === []) {
            return [
                'work_date' => $date,
                'work_date_formatted' => $day->format('d.m.Y'),
                'rows' => [],
                'summary' => [
                    'businesses' => 0,
                    'shifts' => 0,
                    'required' => 0,
                    'assigned' => 0,
                    'missing' => 0,
                ],
            ];
        }

        $businesses = Business::query()
            ->whereIn('id', $businessIdsWithShiftToday)
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get()
            ->keyBy('id');

        $attendances = BusinessShiftAttendance::query()
            ->whereDate('work_date', $date)
            ->whereIn('business_shift_id', $todayShifts->pluck('id'))
            ->whereIn('status', ['in_progress', 'completed'])
            ->get()
            ->groupBy(fn (BusinessShiftAttendance $row) => $row->business_shift_id.'|'.$row->courier_id);

        $shiftsByBusiness = $todayShifts->groupBy('business_id');
        $rows = [];

        foreach ($businessIdsWithShiftToday as $businessId) {
            /** @var Business|null $business */
            $business = $businesses->get($businessId);
            if ($business === null) {
                continue;
            }

            /** @var Collection<int, BusinessShift> $businessShifts */
            $businessShifts = $shiftsByBusiness->get($businessId, collect());
            $todayShiftRows = [];
            $requiredTotal = 0;
            $assignedTotal = 0;
            $missingAssignmentsTotal = 0;
            $startedTotal = 0;
            $operationalShortageTotal = 0;

            foreach ($businessShifts as $shift) {
                $shiftDetail = $this->buildTodayShiftDetail(
                    $shift,
                    $day,
                    $now,
                    $isFuture,
                    $attendances,
                );

                $todayShiftRows[] = $shiftDetail;
                $requiredTotal += $shiftDetail['required'];
                $assignedTotal += $shiftDetail['assigned'];
                $missingAssignmentsTotal += $shiftDetail['missing_assignments'];
                $startedTotal += $shiftDetail['started'];
                $operationalShortageTotal += $shiftDetail['operational_shortage'];
            }

            $rows[] = [
                'business_id' => $business->id,
                'business_name' => $business->displayName(),
                'business_url' => route('businesses.show', $business->id),
                'shift_count' => count($todayShiftRows),
                'required_count' => $requiredTotal,
                'assigned_count' => $assignedTotal,
                'started_count' => $startedTotal,
                'missing_assignments' => $missingAssignmentsTotal,
                'missing_count' => $operationalShortageTotal,
                'operational_shortage' => $operationalShortageTotal,
                'today_shifts' => $todayShiftRows,
            ];
        }

        return [
            'work_date' => $date,
            'work_date_formatted' => $day->format('d.m.Y'),
            'rows' => $rows,
            'summary' => [
                'businesses' => count($rows),
                'shifts' => (int) collect($rows)->sum('shift_count'),
                'required' => (int) collect($rows)->sum('required_count'),
                'assigned' => (int) collect($rows)->sum('assigned_count'),
                'missing' => (int) collect($rows)->sum('missing_count'),
            ],
        ];
    }

    /**
     * @param  Collection<string, Collection<int, BusinessShiftAttendance>>  $attendances
     * @return array<string, mixed>
     */
    private function buildTodayShiftDetail(
        BusinessShift $shift,
        Carbon $day,
        Carbon $now,
        bool $isFuture,
        Collection $attendances,
    ): array {
        $required = max(1, (int) $shift->required_headcount);
        $shiftStart = ShiftAttendanceRules::shiftStartAt($shift, $day);
        $roster = $shift->rosterCouriers->sortBy('full_name')->values();
        $assigned = $roster->count();
        $missingAssignments = max(0, $required - $assigned);

        $couriers = [];
        $started = 0;
        $lateCount = 0;
        $notStartedCount = 0;

        foreach ($roster as $courier) {
            /** @var BusinessShiftAttendance|null $attendance */
            $attendance = $attendances->get($shift->id.'|'.$courier->id)?->first();
            $status = $this->courierLiveStatus($attendance, $shiftStart, $now, $isFuture);
            $lateMinutes = null;

            if ($attendance?->started_at !== null && $attendance->started_at->gt($shiftStart)) {
                $lateMinutes = (int) $shiftStart->diffInMinutes($attendance->started_at, absolute: true);
            }

            if (in_array($status, ['active', 'late', 'completed'], true)) {
                $started++;
            }

            if ($status === 'late') {
                $lateCount++;
            }

            if ($status === 'not_started') {
                $notStartedCount++;
            }

            $couriers[] = [
                'id' => $courier->id,
                'name' => $courier->full_name,
                'phone' => $courier->phone ?: '—',
                'status' => $status,
                'status_label' => $this->courierStatusLabel($status, $lateMinutes),
                'late_minutes' => $lateMinutes,
            ];
        }

        $shiftHasStarted = ! $isFuture && $now->gte($shiftStart);
        $operationalShortage = $shiftHasStarted
            ? max(0, $required - $started)
            : $missingAssignments;

        return [
            'id' => $shift->id,
            'time' => $this->formatShiftTime($shift),
            'required' => $required,
            'assigned' => $assigned,
            'started' => $started,
            'missing_assignments' => $missingAssignments,
            'assigned_not_started' => $notStartedCount,
            'late_count' => $lateCount,
            'operational_shortage' => $operationalShortage,
            'has_started' => $shiftHasStarted,
            'summary_label' => $this->shiftSummaryLabel(
                $required,
                $assigned,
                $started,
                $missingAssignments,
                $notStartedCount,
                $operationalShortage,
                $shiftHasStarted,
            ),
            'couriers' => $couriers,
        ];
    }

    private function courierLiveStatus(
        ?BusinessShiftAttendance $attendance,
        Carbon $shiftStart,
        Carbon $now,
        bool $isFuture,
    ): string {
        if ($attendance?->status === 'in_progress') {
            if ($attendance->started_at !== null && $attendance->started_at->gt($shiftStart)) {
                return 'late';
            }

            return 'active';
        }

        if ($attendance?->status === 'completed') {
            if ($attendance->started_at !== null && $attendance->started_at->gt($shiftStart)) {
                return 'late';
            }

            return 'completed';
        }

        if ($isFuture || $now->lt($shiftStart)) {
            if (! $isFuture && $now->gte($shiftStart->copy()->subHour())) {
                return 'starting_soon';
            }

            return 'upcoming';
        }

        return 'not_started';
    }

    private function courierStatusLabel(string $status, ?int $lateMinutes): string
    {
        return match ($status) {
            'active' => 'Aktif',
            'late' => $lateMinutes !== null ? 'Geç · '.$lateMinutes.' dk' : 'Geç',
            'completed' => 'Tamamladı',
            'starting_soon' => 'Yaklaşan',
            'upcoming' => 'Bekliyor',
            'not_started' => 'Girmedi',
            default => $status,
        };
    }

    private function shiftSummaryLabel(
        int $required,
        int $assigned,
        int $started,
        int $missingAssignments,
        int $assignedNotStarted,
        int $operationalShortage,
        bool $shiftHasStarted,
    ): string {
        if (! $shiftHasStarted) {
            if ($missingAssignments > 0) {
                return sprintf('%d/%d atandı · %d kişi eksik', $assigned, $required, $missingAssignments);
            }

            return sprintf('%d/%d atandı', $assigned, $required);
        }

        $parts = [sprintf('%d/%d geldi', $started, $required)];

        if ($operationalShortage > 0) {
            $parts[] = sprintf('%d eksik', $operationalShortage);
        }

        if ($assignedNotStarted > 0) {
            $parts[] = sprintf('%d katılmadı', $assignedNotStarted);
        }

        return implode(' · ', $parts);
    }

    private function formatShiftTime(?BusinessShift $shift): ?string
    {
        if ($shift === null || $shift->start_time === null || $shift->end_time === null) {
            return null;
        }

        $start = substr((string) $shift->start_time, 0, 5);
        $end = substr((string) $shift->end_time, 0, 5);

        return $start.'–'.$end;
    }
}
