<?php

namespace App\Modules\ShiftPlanning\Services;

use App\Modules\ShiftPlanning\Data\ShiftPlanningFormData;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use Carbon\Carbon;

class ShiftPlanningPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(BusinessShift $shift): array
    {
        $start = $this->formatTime($shift->start_time);
        $end = $this->formatTime($shift->end_time);
        $overnight = $this->isOvernight($shift->start_time, $shift->end_time);
        $days = $shift->activeWeekDays();
        $short = ShiftPlanningFormData::weekDayShort();

        $couriersByDate = [];
        foreach ($shift->dayCouriers as $assignment) {
            $date = $assignment->work_date?->toDateString();
            if ($date === null) {
                continue;
            }

            $couriersByDate[$date] ??= [];
            if ($assignment->relationLoaded('courier') && $assignment->courier) {
                $couriersByDate[$date][] = [
                    'id' => $assignment->courier->id,
                    'name' => $assignment->courier->full_name,
                    'phone' => $assignment->courier->phone ?? '—',
                ];
            }
        }

        foreach ($couriersByDate as $date => $couriers) {
            $couriersByDate[$date] = collect($couriers)
                ->unique('id')
                ->sortBy('name')
                ->values()
                ->all();
        }

        return [
            'id' => $shift->id,
            'name' => $shift->name,
            'start_time' => $start,
            'end_time' => $end,
            'start_time_raw' => substr((string) $shift->start_time, 0, 5),
            'end_time_raw' => substr((string) $shift->end_time, 0, 5),
            'start_date' => $shift->start_date?->toDateString(),
            'end_date' => $shift->end_date?->toDateString(),
            'date_range_label' => sprintf(
                '%s – %s',
                $shift->start_date?->format('d.m.Y') ?? '—',
                $shift->end_date?->format('d.m.Y') ?? '—',
            ),
            'time_range' => $overnight
                ? "{$start} – {$end} (ertesi gün)"
                : "{$start} – {$end}",
            'days_of_week' => $days,
            'excluded_dates' => collect($shift->excludedDateList())->values()->all(),
            'days_label' => collect($days)->map(fn (int $day) => $short[$day] ?? (string) $day)->implode(', '),
            'notes' => $shift->notes,
            'is_active' => (bool) $shift->is_active,
            'status_label' => $shift->is_active ? 'Aktif' : 'Pasif',
            'couriers_by_date' => $couriersByDate,
            'color' => $this->colorForId((int) $shift->id),
        ];
    }

    /**
     * @param  array<string, mixed>  $shiftRow
     * @return array<string, mixed>|null
     */
    public function occurrenceForDate(array $shiftRow, string $date): ?array
    {
        $day = Carbon::parse($date);
        $iso = (int) $day->dayOfWeekIso;

        if (! ($shiftRow['is_active'] ?? false)) {
            return null;
        }

        if (! in_array($iso, $shiftRow['days_of_week'] ?? [], true)) {
            return null;
        }

        $startDate = $shiftRow['start_date'] ?? null;
        $endDate = $shiftRow['end_date'] ?? null;

        if ($startDate && $day->lt(Carbon::parse($startDate)->startOfDay())) {
            return null;
        }

        if ($endDate && $day->gt(Carbon::parse($endDate)->startOfDay())) {
            return null;
        }

        $excluded = $shiftRow['excluded_dates'] ?? [];
        if (in_array($date, $excluded, true)) {
            return null;
        }

        $couriers = $shiftRow['couriers_by_date'][$date] ?? [];

        return [
            ...$shiftRow,
            'work_date' => $date,
            'couriers' => $couriers,
            'courier_ids' => collect($couriers)->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'courier_count' => count($couriers),
        ];
    }

    private function colorForId(int $id): string
    {
        $palette = [
            'bg-emerald-50 border-emerald-200 text-emerald-900',
            'bg-sky-50 border-sky-200 text-sky-900',
            'bg-amber-50 border-amber-200 text-amber-900',
            'bg-violet-50 border-violet-200 text-violet-900',
            'bg-rose-50 border-rose-200 text-rose-900',
            'bg-teal-50 border-teal-200 text-teal-900',
        ];

        return $palette[$id % count($palette)];
    }

    private function formatTime(mixed $time): string
    {
        if ($time === null || $time === '') {
            return '—';
        }

        return Carbon::parse((string) $time)->format('H:i');
    }

    private function isOvernight(mixed $start, mixed $end): bool
    {
        if ($start === null || $end === null) {
            return false;
        }

        return Carbon::parse((string) $end)->format('H:i:s')
            < Carbon::parse((string) $start)->format('H:i:s');
    }
}
