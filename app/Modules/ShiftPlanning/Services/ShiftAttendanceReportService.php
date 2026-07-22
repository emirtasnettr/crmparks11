<?php

namespace App\Modules\ShiftPlanning\Services;

use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Support\ShiftAttendanceRules;
use Carbon\Carbon;

class ShiftAttendanceReportService
{
    public function __construct(
        private readonly ShiftAttendancePresenter $presenter,
    ) {}

    /**
     * @param  list<string>  $statuses  Boş ise tüm durumlar
     * @return array{
     *     from: string,
     *     to: string,
     *     range_label: string,
     *     rows: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function report(Carbon $from, Carbon $to, array $statuses = []): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $rows = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            array_push($rows, ...$this->rowsForDay($day));
        }

        if ($statuses !== []) {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row): bool => in_array($row['status'], $statuses, true)
            ));
        }

        usort($rows, function (array $a, array $b): int {
            return strcmp($a['work_date'], $b['work_date'])
                ?: $a['shift_start_sort'] <=> $b['shift_start_sort']
                ?: strcmp($a['business_name'], $b['business_name'])
                ?: strcmp($a['courier_name'], $b['courier_name']);
        });

        $summary = [
            'all' => count($rows),
            'missing' => count(array_filter($rows, fn (array $r) => $r['status'] === 'missing')),
            'late' => count(array_filter($rows, fn (array $r) => $r['status'] === 'late')),
            'in_progress' => count(array_filter($rows, fn (array $r) => $r['status'] === 'in_progress')),
            'completed' => count(array_filter($rows, fn (array $r) => $r['status'] === 'completed')),
            'planned' => count(array_filter($rows, fn (array $r) => $r['status'] === 'planned')),
        ];

        $rangeLabel = $from->equalTo($to)
            ? $from->format('d.m.Y')
            : $from->format('d.m.Y').' – '.$to->format('d.m.Y');

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'range_label' => $rangeLabel,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    /**
     * @param  list<string>  $statuses
     * @return array{headings: list<string>, rows: list<list<mixed>>}
     */
    public function exportSheet(Carbon $from, Carbon $to, array $statuses = []): array
    {
        $report = $this->report($from, $to, $statuses);

        return [
            'headings' => [
                'Tarih',
                'Kurye',
                'Telefon',
                'İşletme',
                'İl / İlçe',
                'Planlanan saat',
                'Giriş',
                'Çıkış',
                'Durum',
                'Geç (dk)',
                'Çalışılan süre',
                'Hakediş',
            ],
            'rows' => collect($report['rows'])
                ->map(fn (array $row) => [
                    $row['work_date_formatted'],
                    $row['courier_name'],
                    $row['phone'],
                    $row['business_name'],
                    $row['business_location'],
                    $row['time_range'],
                    $row['started_at_formatted'],
                    $row['ended_at_formatted'],
                    $row['status_label'],
                    $row['late_minutes'] ?? '',
                    $row['worked_duration_label'],
                    $row['earnings_formatted'],
                ])
                ->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rowsForDay(Carbon $day): array
    {
        $now = now();
        $date = $day->toDateString();
        $isFuture = ShiftAttendanceRules::isFutureDay($day, $now);

        $shifts = BusinessShift::query()
            ->with(['business.city', 'business.district', 'rosterCouriers'])
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->filter(fn (BusinessShift $shift) => $shift->runsOn($day))
            ->values();

        $attendances = BusinessShiftAttendance::query()
            ->whereDate('work_date', $date)
            ->whereIn('business_shift_id', $shifts->pluck('id'))
            ->whereIn('status', ['in_progress', 'completed'])
            ->get()
            ->groupBy(fn (BusinessShiftAttendance $row) => $row->business_shift_id.'-'.$row->courier_id);

        $rows = [];

        foreach ($shifts as $shift) {
            $shiftStart = ShiftAttendanceRules::shiftStartAt($shift, $day);

            $business = $shift->business;
            $location = collect([$business?->city?->name, $business?->district?->name])
                ->filter()
                ->implode(' / ');

            foreach ($shift->rosterCouriers as $courier) {
                $key = $shift->id.'-'.$courier->id;
                /** @var BusinessShiftAttendance|null $attendance */
                $attendance = $attendances->get($key)?->first();
                $presented = $attendance ? $this->presenter->row($attendance) : null;

                $lateMinutes = null;
                if ($attendance?->started_at !== null && $attendance->started_at->gt($shiftStart)) {
                    $lateMinutes = (int) $shiftStart->diffInMinutes($attendance->started_at, absolute: true);
                }

                $status = $this->statusKey($attendance, $shiftStart, $now, $isFuture);
                $statusLabel = $this->statusLabel($status, $lateMinutes);

                $rows[] = [
                    'work_date' => $date,
                    'work_date_formatted' => $day->format('d.m.Y'),
                    'courier_id' => $courier->id,
                    'courier_name' => $courier->full_name,
                    'phone' => $courier->phone ?? '—',
                    'business_id' => $shift->business_id,
                    'business_name' => $business?->displayName() ?? '—',
                    'business_location' => $location !== '' ? $location : '—',
                    'shift_id' => $shift->id,
                    'time_range' => $shift->timeRangeLabel(),
                    'shift_start_sort' => $shiftStart->timestamp,
                    'started_at_formatted' => $presented['started_at_formatted'] ?? '—',
                    'ended_at_formatted' => $presented['ended_at_formatted'] ?? '—',
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'late_minutes' => $lateMinutes,
                    'late_minutes_label' => $lateMinutes !== null ? $lateMinutes.' dk' : '—',
                    'worked_duration_label' => $presented['worked_duration_label'] ?? '—',
                    'earnings_formatted' => $presented['earnings_formatted'] ?? '—',
                ];
            }
        }

        return $rows;
    }

    private function statusKey(
        ?BusinessShiftAttendance $attendance,
        Carbon $shiftStart,
        Carbon $now,
        bool $isFuture,
    ): string {
        if ($attendance?->isInProgress()) {
            if ($attendance->started_at !== null && $attendance->started_at->gt($shiftStart)) {
                return 'late';
            }

            return 'in_progress';
        }

        if ($attendance?->isCompleted()) {
            if ($attendance->started_at !== null && $attendance->started_at->gt($shiftStart)) {
                return 'late';
            }

            return 'completed';
        }

        if ($isFuture || $now->lt($shiftStart)) {
            return 'planned';
        }

        return 'missing';
    }

    private function statusLabel(string $status, ?int $lateMinutes): string
    {
        return match ($status) {
            'missing' => 'Girmedi',
            'late' => $lateMinutes !== null ? 'Geç - '.$lateMinutes.' dk' : 'Geç',
            'in_progress' => 'Devam ediyor',
            'completed' => 'Geldi',
            'planned' => 'Planlandı',
            default => $status,
        };
    }
}
