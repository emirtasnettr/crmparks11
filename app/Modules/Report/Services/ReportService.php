<?php

namespace App\Modules\Report\Services;

use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
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
     *     summary: array{businesses: int, planned: int, active: int, roster: int, missing: int}
     * }
     */
    public function radar(?Carbon $day = null): array
    {
        $day ??= Carbon::today();
        $date = $day->toDateString();

        $todayShifts = BusinessShift::query()
            ->with(['rosterCouriers:id,full_name,phone'])
            ->where('is_active', true)
            ->get()
            ->filter(fn (BusinessShift $shift) => $shift->runsOn($day))
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
                    'planned' => 0,
                    'active' => 0,
                    'roster' => 0,
                    'missing' => 0,
                ],
            ];
        }

        $businesses = Business::query()
            ->whereIn('id', $businessIdsWithShiftToday)
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get();

        $activePeople = $this->activeCouriersByBusiness($date, $businessIdsWithShiftToday);
        $upcomingPeople = $this->upcomingCouriersByBusiness($todayShifts, $day);

        $rows = [];
        foreach ($businesses as $business) {
            // Planlanmış = restoran için ihtiyaç duyulan kurye sayısı (kapasite; kişi listesi yok).
            $planned = max(0, (int) ($business->planned_courier_count ?? 0));
            if ($planned <= 0) {
                continue;
            }

            $activePeopleForBusiness = array_values($activePeople[$business->id] ?? []);
            $upcomingPeopleForBusiness = array_values($upcomingPeople[$business->id] ?? []);

            $activeIds = collect($activePeopleForBusiness)->pluck('id')->all();

            // Yaklaşan: gelecek saatlerde başlayacak vardiyadakiler; aktiflerle çakışmaz.
            $upcomingUnique = collect($upcomingPeopleForBusiness)
                ->reject(fn (array $person): bool => in_array($person['id'], $activeIds, true))
                ->values()
                ->all();

            // Vardiyada öncelikli; kalan kapasite Yaklaşan'a gider. Toplam ≤ Planlanmış.
            $activePeopleCapped = array_slice($activePeopleForBusiness, 0, $planned);
            $active = count($activePeopleCapped);
            $remainingCapacity = max(0, $planned - $active);
            $upcomingPeopleCapped = array_slice($upcomingUnique, 0, $remainingCapacity);
            $upcoming = count($upcomingPeopleCapped);
            $missing = max(0, $planned - $active - $upcoming);

            $rows[] = [
                'business_id' => $business->id,
                'business_name' => $business->displayName(),
                'planned_courier_count' => $planned,
                'active_on_shift_count' => $active,
                'roster_planned_count' => $upcoming,
                'missing_courier_count' => $missing,
                'active_couriers' => $activePeopleCapped,
                'roster_couriers' => $upcomingPeopleCapped,
            ];
        }

        return [
            'work_date' => $date,
            'work_date_formatted' => $day->format('d.m.Y'),
            'rows' => $rows,
            'summary' => [
                'businesses' => count($rows),
                'planned' => (int) collect($rows)->sum('planned_courier_count'),
                'active' => (int) collect($rows)->sum('active_on_shift_count'),
                'roster' => (int) collect($rows)->sum('roster_planned_count'),
                'missing' => (int) collect($rows)->sum('missing_courier_count'),
            ],
        ];
    }

    /**
     * @param  list<int>  $businessIds
     * @return array<int, array<int, array{id: int, name: string, phone: string, shift_name: string|null, shift_time: string|null}>>
     */
    private function activeCouriersByBusiness(string $date, array $businessIds = []): array
    {
        $rows = BusinessShiftAttendance::query()
            ->with(['courier:id,full_name,phone', 'shift:id,name,start_time,end_time'])
            ->whereDate('work_date', $date)
            ->where('status', 'in_progress')
            ->when($businessIds !== [], fn ($query) => $query->whereIn('business_id', $businessIds))
            ->get();

        return $rows
            ->groupBy('business_id')
            ->map(function (Collection $group): array {
                return $group
                    ->unique('courier_id')
                    ->map(fn (BusinessShiftAttendance $row) => $this->courierPayload(
                        $row->courier,
                        $row->shift,
                    ))
                    ->filter()
                    ->sortBy('name')
                    ->values()
                    ->all();
            })
            ->all();
    }

    /**
     * Gelecek saatlerde başlayacak (henüz start olmamış) vardiya kadrolarındaki kuryeler.
     *
     * @param  Collection<int, BusinessShift>  $todayShifts
     * @return array<int, array<int, array{id: int, name: string, phone: string, shift_name: string|null, shift_time: string|null}>>
     */
    private function upcomingCouriersByBusiness(Collection $todayShifts, Carbon $day): array
    {
        $now = now();
        $byBusiness = [];

        foreach ($todayShifts as $shift) {
            $shiftStart = ShiftAttendanceRules::shiftStartAt($shift, $day);
            if ($now->gte($shiftStart)) {
                continue;
            }

            $businessId = (int) $shift->business_id;
            foreach ($shift->rosterCouriers as $courier) {
                $existing = $byBusiness[$businessId][$courier->id] ?? null;
                $payload = $this->courierPayload($courier, $shift);
                if ($payload === null) {
                    continue;
                }

                $payload['shift_start_ts'] = $shiftStart->timestamp;

                // Aynı kurye birden fazla yaklaşan vardiyadaysa en erken olanı tut.
                if ($existing === null || $payload['shift_start_ts'] < ($existing['shift_start_ts'] ?? PHP_INT_MAX)) {
                    $byBusiness[$businessId][$courier->id] = $payload;
                }
            }
        }

        return collect($byBusiness)
            ->map(function (array $people): array {
                return collect($people)
                    ->map(function (array $person): array {
                        unset($person['shift_start_ts']);

                        return $person;
                    })
                    ->sortBy('name')
                    ->values()
                    ->all();
            })
            ->all();
    }

    /**
     * @return array{id: int, name: string, phone: string, shift_name: string|null, shift_time: string|null}|null
     */
    private function courierPayload(?Courier $courier, ?BusinessShift $shift): ?array
    {
        if ($courier === null) {
            return null;
        }

        return [
            'id' => $courier->id,
            'name' => $courier->full_name,
            'phone' => $courier->phone ?: '—',
            'shift_name' => $shift?->name,
            'shift_time' => $this->formatShiftTime($shift),
        ];
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
