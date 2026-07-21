<?php

namespace App\Modules\ShiftPlanning\Services;

use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Support\ShiftAttendanceRules;

class ShiftAttendancePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function row(BusinessShiftAttendance $attendance): array
    {
        $attendance->loadMissing(['shift', 'business']);

        return [
            'id' => $attendance->id,
            'shift_id' => $attendance->business_shift_id,
            'shift_name' => $attendance->shift?->name ?? '—',
            'business_id' => $attendance->business_id,
            'business_name' => $attendance->business?->displayName() ?? '—',
            'work_date' => $attendance->work_date?->toDateString(),
            'work_date_formatted' => $attendance->work_date?->format('d.m.Y') ?? '—',
            'started_at' => $attendance->started_at?->toDateTimeString(),
            'started_at_formatted' => $attendance->started_at?->format('d.m.Y H:i') ?? '—',
            'ended_at' => $attendance->ended_at?->toDateTimeString(),
            'ended_at_formatted' => $attendance->ended_at?->format('d.m.Y H:i') ?? '—',
            'status' => $attendance->status,
            'status_label' => match ($attendance->status) {
                'in_progress' => 'Devam ediyor',
                'completed' => 'Geldi',
                'cancelled' => 'İptal',
                default => $attendance->status,
            },
            'worked_minutes' => (int) ($attendance->worked_minutes ?? 0),
            'package_count' => $attendance->package_count !== null ? (int) $attendance->package_count : null,
            'worked_hours' => $attendance->workedHours(),
            'worked_duration_label' => $this->durationLabel($attendance->worked_minutes),
            'hourly_rate' => $attendance->hourly_rate !== null ? (float) $attendance->hourly_rate : null,
            'hourly_rate_formatted' => $attendance->hourly_rate !== null
                ? number_format((float) $attendance->hourly_rate, 2, ',', '.').' ₺/saat'
                : '—',
            'earnings_amount' => $attendance->earnings_amount !== null ? (float) $attendance->earnings_amount : null,
            'earnings_formatted' => $attendance->earnings_amount !== null
                ? number_format((float) $attendance->earnings_amount, 2, ',', '.').' ₺'
                : '—',
            'pricing_model' => $attendance->pricing_model,
            'is_hourly' => $attendance->pricing_model === 'hourly',
            'end_reason' => $attendance->end_reason,
            'end_reason_label' => ShiftAttendanceRules::endReasonLabel($attendance->end_reason),
            'can_end' => $attendance->isInProgress(),
        ];
    }

    private function durationLabel(?int $minutes): string
    {
        $minutes = (int) ($minutes ?? 0);

        if ($minutes <= 0) {
            return '—';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours === 0) {
            return "{$mins} dk";
        }

        if ($mins === 0) {
            return "{$hours} sa";
        }

        return "{$hours} sa {$mins} dk";
    }
}
