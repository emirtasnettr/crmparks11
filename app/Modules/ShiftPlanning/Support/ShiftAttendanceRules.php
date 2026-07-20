<?php

namespace App\Modules\ShiftPlanning\Support;

use App\Modules\ShiftPlanning\Models\BusinessShift;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Vardiya katılım zaman pencereleri ve hakediş süresi kuralları.
 *
 * - Başlatma: vardiya başlangıcından en erken 15 dk önce
 * - Otomatik bitiş: vardiya bitişinden 30 dk sonra
 * - Hakediş: yalnızca planlanan vardiya süresi (erken/geç buffer dahil değil)
 */
final class ShiftAttendanceRules
{
    public const EARLY_START_MINUTES = 15;

    public const AUTO_END_GRACE_MINUTES = 30;

    public static function shiftStartAt(BusinessShift $shift, CarbonInterface $workDate): Carbon
    {
        return Carbon::parse($workDate->toDateString().' '.substr((string) $shift->start_time, 0, 8));
    }

    public static function shiftEndAt(BusinessShift $shift, CarbonInterface $workDate): Carbon
    {
        $start = self::shiftStartAt($shift, $workDate);
        $end = Carbon::parse($workDate->toDateString().' '.substr((string) $shift->end_time, 0, 8));

        if ($end->lte($start)) {
            $end->addDay();
        }

        return $end;
    }

    public static function earliestStartAt(BusinessShift $shift, CarbonInterface $workDate): Carbon
    {
        return self::shiftStartAt($shift, $workDate)->copy()->subMinutes(self::EARLY_START_MINUTES);
    }

    public static function autoEndAt(BusinessShift $shift, CarbonInterface $workDate): Carbon
    {
        return self::shiftEndAt($shift, $workDate)->copy()->addMinutes(self::AUTO_END_GRACE_MINUTES);
    }

    /** Planlanan vardiya süresi (dakika) — hakediş için. */
    public static function scheduledMinutes(BusinessShift $shift, CarbonInterface $workDate): int
    {
        $start = self::shiftStartAt($shift, $workDate);
        $end = self::shiftEndAt($shift, $workDate);

        return max(1, (int) $start->diffInMinutes($end, absolute: true));
    }

    public static function isFutureDay(CarbonInterface $workDate, ?CarbonInterface $now = null): bool
    {
        $now ??= now();

        return $workDate->copy()->startOfDay()->gt($now->copy()->startOfDay());
    }

    public static function isWithinCourierStartWindow(BusinessShift $shift, CarbonInterface $workDate, ?CarbonInterface $now = null): bool
    {
        $now ??= now();

        if (self::isFutureDay($workDate, $now)) {
            return false;
        }

        return $now->gte(self::earliestStartAt($shift, $workDate))
            && $now->lte(self::shiftEndAt($shift, $workDate));
    }

    public static function shouldAutoEnd(BusinessShift $shift, CarbonInterface $workDate, ?CarbonInterface $now = null): bool
    {
        $now ??= now();

        return $now->gte(self::autoEndAt($shift, $workDate));
    }

    /**
     * @return 'planned'|'in_progress'|'completed'|'missing'
     */
    public static function participationStatus(?string $attendanceStatus, CarbonInterface $workDate, ?CarbonInterface $now = null): string
    {
        if ($attendanceStatus === 'in_progress') {
            return 'in_progress';
        }

        if ($attendanceStatus === 'completed') {
            return 'completed';
        }

        if (self::isFutureDay($workDate, $now)) {
            return 'planned';
        }

        return 'missing';
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'planned' => 'Planlandı',
            'in_progress' => 'Devam ediyor',
            'completed' => 'Geldi',
            'missing' => 'Gelmedi',
            default => $status,
        };
    }
}
