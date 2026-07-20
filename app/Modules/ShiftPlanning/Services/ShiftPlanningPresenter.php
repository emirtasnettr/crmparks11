<?php

namespace App\Modules\ShiftPlanning\Services;

use App\Modules\ShiftPlanning\Data\ShiftPlanningFormData;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftJokerAssignment;
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
        $required = max(1, (int) $shift->required_headcount);

        $couriers = $shift->rosterCouriers
            ->map(fn ($courier) => [
                'id' => $courier->id,
                'name' => $courier->full_name,
                'phone' => $courier->phone ?? '—',
            ])
            ->values()
            ->all();

        $assigned = count($couriers);

        return [
            'id' => $shift->id,
            'name' => $shift->name,
            'start_time' => $start,
            'end_time' => $end,
            'start_time_raw' => substr((string) $shift->start_time, 0, 5),
            'end_time_raw' => substr((string) $shift->end_time, 0, 5),
            'start_date' => $shift->start_date?->toDateString(),
            'end_date' => $shift->end_date?->toDateString(),
            'date_range_label' => ($shift->start_date && $shift->end_date)
                ? $shift->start_date->format('d.m.Y').' – '.$shift->end_date->format('d.m.Y')
                : null,
            'time_range' => $overnight
                ? "{$start} – {$end} (ertesi gün)"
                : "{$start} – {$end}",
            'required_headcount' => $required,
            'assigned_count' => $assigned,
            'is_understaffed' => $assigned < $required,
            'staffing_label' => "{$assigned} / {$required} kişi",
            'notes' => $shift->notes,
            'is_active' => (bool) $shift->is_active,
            'status_label' => $shift->is_active ? 'Aktif' : 'Pasif',
            'couriers' => $couriers,
            'courier_ids' => collect($couriers)->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'color' => $this->colorForId((int) $shift->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jokerRow(BusinessShiftJokerAssignment $assignment): array
    {
        $reasons = ShiftPlanningFormData::jokerReasons();

        return [
            'id' => $assignment->id,
            'shift_id' => $assignment->business_shift_id,
            'shift_name' => $assignment->shift?->name ?? '—',
            'work_date' => $assignment->work_date?->toDateString(),
            'work_date_formatted' => $assignment->work_date?->format('d.m.Y') ?? '—',
            'absent_courier_id' => $assignment->absent_courier_id,
            'absent_courier_name' => $assignment->absentCourier?->full_name ?? '—',
            'joker_courier_id' => $assignment->joker_courier_id,
            'joker_courier_name' => $assignment->jokerCourier?->full_name ?? '—',
            'reason' => $assignment->reason,
            'reason_label' => $reasons[$assignment->reason] ?? $assignment->reason,
            'notes' => $assignment->notes,
        ];
    }

    /**
     * @param  array<string, mixed>  $shiftRow
     * @param  array<int, array<string, mixed>>  $jokersForDate
     * @return array<string, mixed>
     */
    public function dayOccurrence(array $shiftRow, string $date, array $jokersForDate = []): array
    {
        $absentIds = collect($jokersForDate)
            ->where('shift_id', $shiftRow['id'])
            ->pluck('absent_courier_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $jokers = collect($jokersForDate)
            ->where('shift_id', $shiftRow['id'])
            ->values()
            ->all();

        $working = collect($shiftRow['couriers'] ?? [])
            ->reject(fn (array $courier) => in_array((int) $courier['id'], $absentIds, true))
            ->values()
            ->all();

        foreach ($jokers as $joker) {
            $working[] = [
                'id' => $joker['joker_courier_id'],
                'name' => $joker['joker_courier_name'],
                'phone' => '—',
                'is_joker' => true,
                'covers' => $joker['absent_courier_name'],
                'reason_label' => $joker['reason_label'],
            ];
        }

        return [
            ...$shiftRow,
            'work_date' => $date,
            'working_couriers' => $working,
            'jokers' => $jokers,
            'absent_count' => count($absentIds),
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
