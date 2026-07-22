<?php

namespace App\Modules\ShiftPlanning\Services;

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
        $missingAssignments = max(0, $required - $assigned);

        return [
            'id' => $shift->id,
            'business_id' => (int) $shift->business_id,
            'start_time' => $start,
            'end_time' => $end,
            'start_time_raw' => substr((string) $shift->start_time, 0, 5),
            'end_time_raw' => substr((string) $shift->end_time, 0, 5),
            'start_date' => $shift->start_date?->toDateString(),
            'end_date' => $shift->end_date?->toDateString(),
            'date_range_label' => ($shift->start_date && $shift->end_date)
                ? $shift->start_date->format('d.m.Y').' – '.$shift->end_date->format('d.m.Y')
                : null,
            'time_range' => "{$start} – {$end}",
            'required_headcount' => $required,
            'assigned_count' => $assigned,
            'missing_assignments' => $missingAssignments,
            'is_understaffed' => $missingAssignments > 0,
            'staffing_label' => $missingAssignments > 0
                ? "{$assigned}/{$required} atandı · {$missingAssignments} eksik"
                : "{$assigned}/{$required} kişi",
            'notes' => $shift->notes,
            'is_active' => (bool) $shift->is_active,
            'status_label' => $shift->is_active ? 'Aktif' : 'Pasif',
            'couriers' => $couriers,
            'courier_ids' => collect($couriers)->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'color' => $this->colorForId((int) $shift->id),
        ];
    }

    /**
     * @param  array<string, mixed>  $shiftRow
     * @return array<string, mixed>
     */
    public function dayOccurrence(array $shiftRow, string $date): array
    {
        $working = collect($shiftRow['couriers'] ?? [])
            ->values()
            ->all();

        return [
            ...$shiftRow,
            'work_date' => $date,
            'working_couriers' => $working,
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
}
