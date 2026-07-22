<?php

namespace App\Modules\ShiftPlanning\Support;

use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Aynı kuryenin aynı anda iki farklı vardiyada olmamasını doğrular.
 * İşletme ataması gerekmez; yalnızca tarih + saat çakışması engellenir.
 */
final class ShiftCourierConflictChecker
{
    /**
     * @param  array<int, int>  $courierIds
     * @param  array{
     *     start_date?: mixed,
     *     end_date?: mixed,
     *     start_time?: mixed,
     *     end_time?: mixed,
     *     days_of_week?: mixed,
     *     excluded_dates?: mixed,
     * }  $schedule
     */
    public function assertNoRosterConflicts(
        array $courierIds,
        array $schedule,
        ?int $excludeShiftId = null,
        string $errorKey = 'courier_ids',
    ): void {
        foreach ($courierIds as $courierId) {
            $conflict = $this->firstConflictForCourier((int) $courierId, $schedule, $excludeShiftId);

            if ($conflict === null) {
                continue;
            }

            $courierName = Courier::query()->whereKey($courierId)->value('full_name') ?? ('#'.$courierId);

            throw ValidationException::withMessages([
                $errorKey => sprintf(
                    '%s bu saatlerde başka bir vardiyada (%s · %s–%s). Aynı anda farklı yerde vardiya tanımlanamaz.',
                    $courierName,
                    $conflict['business'],
                    $conflict['start_time'],
                    $conflict['end_time'],
                ),
            ]);
        }
    }

    /**
     * Verilen programda çakışması olan kurye id'leri.
     *
     * @param  array<int, int>  $courierIds
     * @param  array{
     *     start_date?: mixed,
     *     end_date?: mixed,
     *     start_time?: mixed,
     *     end_time?: mixed,
     *     days_of_week?: mixed,
     *     excluded_dates?: mixed,
     * }  $schedule
     * @return array<int, int>
     */
    public function busyCourierIds(array $courierIds, array $schedule, ?int $excludeShiftId = null): array
    {
        $busy = [];

        foreach ($courierIds as $courierId) {
            $id = (int) $courierId;
            if ($id <= 0) {
                continue;
            }

            if ($this->firstConflictForCourier($id, $schedule, $excludeShiftId) !== null) {
                $busy[] = $id;
            }
        }

        return $busy;
    }

    /**
     * @param  array{
     *     start_date?: mixed,
     *     end_date?: mixed,
     *     start_time?: mixed,
     *     end_time?: mixed,
     *     days_of_week?: mixed,
     *     excluded_dates?: mixed,
     * }  $schedule
     * @return array{business: string, shift: string, start_time: string, end_time: string}|null
     */
    private function firstConflictForCourier(int $courierId, array $schedule, ?int $excludeShiftId): ?array
    {
        $probe = $this->scheduleAsShift($schedule);
        $rangeStart = $probe->start_date?->copy()->startOfDay() ?? now()->startOfDay();
        $rangeEnd = $probe->end_date?->copy()->startOfDay() ?? $rangeStart->copy();

        if ($rangeEnd->lt($rangeStart)) {
            return null;
        }

        $others = $this->otherCommitments($courierId, $excludeShiftId, $rangeStart, $rangeEnd);

        $cursor = $rangeStart->copy();
        $guard = 0;

        while ($cursor->lte($rangeEnd) && $guard < 400) {
            if ($probe->runsOn($cursor)) {
                foreach ($others as $other) {
                    if ($this->intervalsOverlapOnDate($probe, $other['shift'], $cursor)) {
                        return $this->conflictPayload($other['shift']);
                    }
                }
            }

            $cursor->addDay();
            $guard++;
        }

        return null;
    }

    /**
     * @return list<array{shift: BusinessShift}>
     */
    private function otherCommitments(
        int $courierId,
        ?int $excludeShiftId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        /** @var Collection<int, BusinessShift> $rosterShifts */
        $rosterShifts = BusinessShift::query()
            ->with('business')
            ->whereHas('rosterCouriers', fn ($query) => $query->where('couriers.id', $courierId))
            ->when($excludeShiftId !== null, fn ($query) => $query->where('id', '!=', $excludeShiftId))
            ->where(function ($query) use ($from, $to): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $from->toDateString());
            })
            ->where(function ($query) use ($from, $to): void {
                $query->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $to->toDateString());
            })
            ->get();

        return $rosterShifts
            ->map(fn (BusinessShift $shift) => ['shift' => $shift])
            ->all();
    }

    private function intervalsOverlapOnDate(
        BusinessShift $a,
        BusinessShift $b,
        CarbonInterface $date,
    ): bool {
        $day = $date->copy()->startOfDay();

        foreach ($this->intervalsCoveringDate($a, $day) as $intervalA) {
            foreach ($this->intervalsCoveringDate($b, $day) as $intervalB) {
                if ($intervalA[0]->lt($intervalB[1]) && $intervalB[0]->lt($intervalA[1])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function intervalsCoveringDate(BusinessShift $shift, CarbonInterface $date): array
    {
        $day = $date->copy()->startOfDay();
        $intervals = [];

        if ($shift->runsOn($day)) {
            $start = $this->atTime($day, $shift->start_time);
            $end = $this->atTime($day, $shift->end_time);

            if ($end->lte($start)) {
                $intervals[] = [$start, $day->copy()->addDay()->startOfDay()];
            } else {
                $intervals[] = [$start, $end];
            }
        }

        $previous = $day->copy()->subDay();
        if ($shift->runsOn($previous)) {
            $start = $this->atTime($previous, $shift->start_time);
            $end = $this->atTime($previous, $shift->end_time);

            if ($end->lte($start)) {
                $intervals[] = [$day->copy()->startOfDay(), $this->atTime($day, $shift->end_time)];
            }
        }

        return $intervals;
    }

    private function atTime(CarbonInterface $date, mixed $time): Carbon
    {
        $raw = substr((string) $time, 0, 8);

        return Carbon::parse($date->toDateString().' '.$raw);
    }

    /**
     * @param  array{
     *     start_date?: mixed,
     *     end_date?: mixed,
     *     start_time?: mixed,
     *     end_time?: mixed,
     *     days_of_week?: mixed,
     *     excluded_dates?: mixed,
     * }  $schedule
     */
    private function scheduleAsShift(array $schedule): BusinessShift
    {
        $shift = new BusinessShift;
        $shift->start_time = $schedule['start_time'] ?? '00:00';
        $shift->end_time = $schedule['end_time'] ?? '00:00';
        $shift->start_date = isset($schedule['start_date'])
            ? Carbon::parse((string) $schedule['start_date'])->startOfDay()
            : null;
        $shift->end_date = isset($schedule['end_date'])
            ? Carbon::parse((string) $schedule['end_date'])->startOfDay()
            : null;
        $shift->days_of_week = $schedule['days_of_week'] ?? null;
        $shift->excluded_dates = $schedule['excluded_dates'] ?? [];

        return $shift;
    }

    /**
     * @return array{business: string, start_time: string, end_time: string}
     */
    private function conflictPayload(BusinessShift $shift): array
    {
        return [
            'business' => $shift->business?->displayName() ?? ('İşletme #'.$shift->business_id),
            'start_time' => substr((string) $shift->start_time, 0, 5),
            'end_time' => substr((string) $shift->end_time, 0, 5),
        ];
    }
}
