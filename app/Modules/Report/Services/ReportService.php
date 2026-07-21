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
    private const WEEKDAY_LABELS = [
        0 => 'Pazar',
        1 => 'Pazartesi',
        2 => 'Salı',
        3 => 'Çarşamba',
        4 => 'Perşembe',
        5 => 'Cuma',
        6 => 'Cumartesi',
    ];

    private const MONTH_LABELS = [
        1 => 'Oca',
        2 => 'Şub',
        3 => 'Mar',
        4 => 'Nis',
        5 => 'May',
        6 => 'Haz',
        7 => 'Tem',
        8 => 'Ağu',
        9 => 'Eyl',
        10 => 'Eki',
        11 => 'Kas',
        12 => 'Ara',
    ];

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
        $weekDates = collect(range(0, 6))
            ->map(fn (int $offset) => $day->copy()->addDays($offset)->startOfDay())
            ->values();

        $allActiveShifts = BusinessShift::query()
            ->with(['rosterCouriers:id,full_name,phone'])
            ->where('is_active', true)
            ->get();

        $todayShifts = $allActiveShifts
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

        $shiftsByBusiness = $allActiveShifts
            ->filter(fn (BusinessShift $shift) => in_array((int) $shift->business_id, $businessIdsWithShiftToday, true))
            ->groupBy('business_id');

        $activePeople = $this->activeCouriersByBusiness($date, $businessIdsWithShiftToday);
        $assignedPeople = $this->assignedCouriersByBusiness($todayShifts, $day);

        $rows = [];
        foreach ($businesses as $business) {
            // Planlanmış = restoran için ihtiyaç duyulan kurye sayısı (kapasite; kişi listesi yok).
            $planned = max(0, (int) ($business->planned_courier_count ?? 0));
            if ($planned <= 0) {
                continue;
            }

            $activePeopleForBusiness = array_values($activePeople[$business->id] ?? []);
            $assignedPeopleForBusiness = array_values($assignedPeople[$business->id] ?? []);

            $activeIds = collect($activePeopleForBusiness)->pluck('id')->all();

            // Atanan: bugünkü vardiya kadrosundaki kuryeler; vardiyada olanlarla çakışmaz.
            $assignedUnique = collect($assignedPeopleForBusiness)
                ->reject(fn (array $person): bool => in_array($person['id'], $activeIds, true))
                ->values()
                ->all();

            // Vardiyada öncelikli; kalan kapasite Atanan'a gider. Toplam ≤ Planlanmış.
            $activePeopleCapped = array_slice($activePeopleForBusiness, 0, $planned);
            $active = count($activePeopleCapped);
            $remainingCapacity = max(0, $planned - $active);
            $assignedPeopleCapped = array_slice($assignedUnique, 0, $remainingCapacity);
            $assigned = count($assignedPeopleCapped);
            $missing = max(0, $planned - $active - $assigned);

            /** @var Collection<int, BusinessShift> $businessShifts */
            $businessShifts = $shiftsByBusiness->get($business->id, collect());

            $rows[] = [
                'business_id' => $business->id,
                'business_name' => $business->displayName(),
                'business_url' => route('businesses.show', $business->id),
                'planned_courier_count' => $planned,
                'active_on_shift_count' => $active,
                'roster_planned_count' => $assigned,
                'missing_courier_count' => $missing,
                'active_couriers' => $activePeopleCapped,
                'roster_couriers' => $assignedPeopleCapped,
                'week_schedule' => $this->weekScheduleForBusiness($businessShifts, $weekDates),
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
     * @param  Collection<int, BusinessShift>  $businessShifts
     * @param  Collection<int, Carbon>  $weekDates
     * @return list<array<string, mixed>>
     */
    private function weekScheduleForBusiness(Collection $businessShifts, Collection $weekDates): array
    {
        $days = [];

        foreach ($weekDates as $index => $date) {
            $dayShifts = $businessShifts
                ->filter(fn (BusinessShift $shift) => $shift->runsOn($date))
                ->sortBy(fn (BusinessShift $shift) => (string) $shift->start_time)
                ->values();

            if ($dayShifts->isEmpty()) {
                continue;
            }

            $shifts = [];
            foreach ($dayShifts as $shift) {
                $couriers = $shift->rosterCouriers
                    ->sortBy('full_name')
                    ->map(fn (Courier $courier) => [
                        'id' => $courier->id,
                        'name' => $courier->full_name,
                        'phone' => $courier->phone ?: '—',
                    ])
                    ->values()
                    ->all();

                $shifts[] = [
                    'id' => $shift->id,
                    'name' => $shift->name ?: 'Vardiya',
                    'time' => $this->formatShiftTime($shift),
                    'courier_count' => count($couriers),
                    'couriers' => $couriers,
                ];
            }

            $days[] = [
                'date' => $date->toDateString(),
                'label' => $this->dayLabel($date, $index === 0),
                'weekday' => self::WEEKDAY_LABELS[(int) $date->dayOfWeek] ?? '',
                'is_today' => $index === 0,
                'shift_count' => count($shifts),
                'shifts' => $shifts,
            ];
        }

        return $days;
    }

    private function dayLabel(Carbon $date, bool $isToday): string
    {
        $weekday = self::WEEKDAY_LABELS[(int) $date->dayOfWeek] ?? '';
        $month = self::MONTH_LABELS[(int) $date->month] ?? $date->format('M');
        $dayMonth = $date->format('j').' '.$month;

        if ($isToday) {
            return 'Bugün · '.$dayMonth;
        }

        if ($date->isTomorrow()) {
            return 'Yarın · '.$dayMonth;
        }

        return $weekday.' · '.$dayMonth;
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
     * Bugünkü vardiya kadrolarına atanmış kuryeler (başlamış / başlamamış fark etmeksizin).
     *
     * @param  Collection<int, BusinessShift>  $todayShifts
     * @return array<int, array<int, array{id: int, name: string, phone: string, shift_name: string|null, shift_time: string|null}>>
     */
    private function assignedCouriersByBusiness(Collection $todayShifts, Carbon $day): array
    {
        $byBusiness = [];

        foreach ($todayShifts as $shift) {
            $businessId = (int) $shift->business_id;
            $shiftStart = ShiftAttendanceRules::shiftStartAt($shift, $day);

            foreach ($shift->rosterCouriers as $courier) {
                $payload = $this->courierPayload($courier, $shift);
                if ($payload === null) {
                    continue;
                }

                $payload['shift_start_ts'] = $shiftStart->timestamp;
                $existing = $byBusiness[$businessId][$courier->id] ?? null;

                // Aynı kurye birden fazla vardiyadaysa en erken olanı tut.
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
