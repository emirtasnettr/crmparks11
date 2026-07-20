<?php

namespace App\Modules\ShiftPlanning\Services;

use App\Models\User;
use App\Modules\Business\Services\BusinessCommercialContractService;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Support\CourierAvatar;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Models\BusinessShiftJokerAssignment;
use App\Modules\ShiftPlanning\Support\ShiftAttendanceRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ShiftAttendanceService
{
    public function __construct(
        private readonly ShiftAttendancePresenter $presenter,
        private readonly BusinessCommercialContractService $commercialContracts,
    ) {}

    public function resolveCourierForUser(User $user): Courier
    {
        $courier = $user->profileable;

        if (! $courier instanceof Courier) {
            throw ValidationException::withMessages([
                'courier' => 'Bu hesap bir kurye profiline bağlı değil.',
            ]);
        }

        return $courier;
    }

    /**
     * @return array{today: list<array<string, mixed>>, recent: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    public function portalPayload(Courier $courier, ?Carbon $day = null): array
    {
        $day ??= Carbon::today();

        return [
            'today' => $this->todayShiftsForCourier($courier, $day)
                ->map(fn (array $row) => $row)
                ->values()
                ->all(),
            'recent' => $this->recentAttendances($courier, 30)
                ->map(fn (BusinessShiftAttendance $attendance) => $this->presenter->row($attendance))
                ->values()
                ->all(),
            'summary' => $this->earningsSummary($courier, $day->copy()->startOfMonth(), $day->copy()->endOfMonth()),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function todayShiftsForCourier(Courier $courier, Carbon $day): Collection
    {
        $rosterShiftIds = DB::table('business_shift_couriers')
            ->where('courier_id', $courier->id)
            ->pluck('business_shift_id');

        $jokerShiftIds = BusinessShiftJokerAssignment::query()
            ->where('joker_courier_id', $courier->id)
            ->whereDate('work_date', $day->toDateString())
            ->pluck('business_shift_id');

        $absentShiftIds = BusinessShiftJokerAssignment::query()
            ->where('absent_courier_id', $courier->id)
            ->whereDate('work_date', $day->toDateString())
            ->pluck('business_shift_id');

        $shiftIds = $rosterShiftIds
            ->reject(fn ($id) => $absentShiftIds->contains($id))
            ->merge($jokerShiftIds)
            ->unique()
            ->values();

        if ($shiftIds->isEmpty()) {
            return collect();
        }

        $shifts = BusinessShift::query()
            ->with(['business.activePricing.pricingModelType'])
            ->whereIn('id', $shiftIds)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->filter(fn (BusinessShift $shift) => $shift->runsOn($day))
            ->values();

        $attendances = BusinessShiftAttendance::query()
            ->where('courier_id', $courier->id)
            ->whereDate('work_date', $day->toDateString())
            ->whereIn('business_shift_id', $shifts->pluck('id'))
            ->whereIn('status', ['in_progress', 'completed'])
            ->get()
            ->keyBy('business_shift_id');

        return $shifts->map(function (BusinessShift $shift) use ($day, $attendances, $jokerShiftIds) {
            $attendance = $attendances->get($shift->id);
            $contract = $this->commercialContracts->forBusinessOnDate((int) $shift->business_id, $day);
            $pricingCode = $contract?->work_type;
            $withinStart = ShiftAttendanceRules::isWithinCourierStartWindow($shift, $day);
            $workTypes = \App\Modules\Business\Data\BusinessCommercialContractFormData::workTypes();

            return [
                'shift_id' => $shift->id,
                'shift_name' => $shift->name,
                'business_id' => $shift->business_id,
                'business_name' => $shift->business?->displayName() ?? '—',
                'start_time' => substr((string) $shift->start_time, 0, 5),
                'end_time' => substr((string) $shift->end_time, 0, 5),
                'work_date' => $day->toDateString(),
                'work_date_formatted' => $day->format('d.m.Y'),
                'is_joker' => $jokerShiftIds->contains($shift->id),
                'pricing_model' => $pricingCode,
                'pricing_model_label' => $pricingCode ? ($workTypes[$pricingCode] ?? $pricingCode) : '—',
                'hourly_rate' => $contract?->courierHourlyRateForAttendance(),
                'attendance' => $attendance ? $this->presenter->row($attendance) : null,
                'can_start' => $attendance === null && $withinStart,
                'can_end' => $attendance?->isInProgress() ?? false,
                'start_window_opens_at' => ShiftAttendanceRules::earliestStartAt($shift, $day)->format('H:i'),
            ];
        });
    }

    /**
     * @param  array{staff_assist?: bool, notes?: string|null, started_at?: Carbon|null}  $options
     */
    public function start(Courier $courier, int $shiftId, ?Carbon $day = null, array $options = []): BusinessShiftAttendance
    {
        $day ??= Carbon::today();
        $staffAssist = (bool) ($options['staff_assist'] ?? false);

        return DB::transaction(function () use ($courier, $shiftId, $day, $options, $staffAssist): BusinessShiftAttendance {
            $shift = $this->assertCourierCanAttend($courier, $shiftId, $day);

            if (! $staffAssist) {
                $open = BusinessShiftAttendance::query()
                    ->where('courier_id', $courier->id)
                    ->where('status', 'in_progress')
                    ->first();

                if ($open !== null) {
                    throw ValidationException::withMessages([
                        'shift' => 'Zaten devam eden bir vardiyanız var. Önce onu sonlandırın.',
                    ]);
                }
            }

            $existing = BusinessShiftAttendance::query()
                ->where('courier_id', $courier->id)
                ->where('business_shift_id', $shift->id)
                ->whereDate('work_date', $day->toDateString())
                ->whereIn('status', ['in_progress', 'completed'])
                ->first();

            if ($existing !== null) {
                throw ValidationException::withMessages([
                    'shift' => 'Bu vardiya için seçilen günde zaten kayıt var.',
                ]);
            }

            if (ShiftAttendanceRules::isFutureDay($day)) {
                throw ValidationException::withMessages([
                    'shift' => 'Gelecek gün vardiyası henüz başlatılamaz.',
                ]);
            }

            if (! $staffAssist && ! ShiftAttendanceRules::isWithinCourierStartWindow($shift, $day)) {
                $opensAt = ShiftAttendanceRules::earliestStartAt($shift, $day)->format('d.m.Y H:i');

                throw ValidationException::withMessages([
                    'shift' => "Vardiya, başlangıç saatinden en erken 15 dakika önce başlatılabilir. Açılış: {$opensAt}",
                ]);
            }

            $contract = $this->commercialContracts->forBusinessOnDate((int) $shift->business_id, $day);
            $pricingCode = $contract?->work_type;
            $hourlyRate = $contract?->courierHourlyRateForAttendance();

            $startedAt = $options['started_at'] ?? null;
            if (! $startedAt instanceof Carbon) {
                $startedAt = now();
            }

            return BusinessShiftAttendance::query()->create([
                'business_shift_id' => $shift->id,
                'business_id' => $shift->business_id,
                'commercial_contract_id' => $contract?->id,
                'courier_id' => $courier->id,
                'work_date' => $day->toDateString(),
                'started_at' => $startedAt,
                'status' => 'in_progress',
                'worked_minutes' => 0,
                'hourly_rate' => $hourlyRate,
                'pricing_model' => $pricingCode,
                'notes' => $options['notes'] ?? null,
            ]);
        });
    }

    public function startForCourier(Courier $courier, int $shiftId, Carbon $day, User $staff, ?string $note = null): BusinessShiftAttendance
    {
        $staffNote = 'Personel müdahalesi: '.$staff->name.' başlattı';
        if (filled($note)) {
            $staffNote .= ' — '.$note;
        }

        return $this->start($courier, $shiftId, $day, [
            'staff_assist' => true,
            'notes' => $staffNote,
        ]);
    }

    public function end(Courier $courier, int $attendanceId): BusinessShiftAttendance
    {
        return $this->completeAttendance($attendanceId, $courier->id);
    }

    public function endForCourier(int $attendanceId, User $staff, ?string $note = null): BusinessShiftAttendance
    {
        $attendance = $this->completeAttendance($attendanceId, null);

        $staffNote = 'Personel müdahalesi: '.$staff->name.' sonlandırdı';
        if (filled($note)) {
            $staffNote .= ' — '.$note;
        }

        $existingNotes = trim((string) $attendance->notes);
        $attendance->update([
            'notes' => $existingNotes !== '' ? $existingNotes."\n".$staffNote : $staffNote,
        ]);

        return $attendance->fresh(['shift', 'business', 'courier']);
    }

    /**
     * @return Collection<int, BusinessShiftAttendance>
     */
    public function recentAttendances(Courier $courier, int $days = 30): Collection
    {
        return BusinessShiftAttendance::query()
            ->with(['shift', 'business'])
            ->where('courier_id', $courier->id)
            ->where('work_date', '>=', now()->subDays($days)->toDateString())
            ->orderByDesc('work_date')
            ->orderByDesc('started_at')
            ->limit(50)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function earningsSummary(Courier $courier, Carbon $from, Carbon $to): array
    {
        $rows = BusinessShiftAttendance::query()
            ->where('courier_id', $courier->id)
            ->where('status', 'completed')
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $totalMinutes = (int) $rows->sum('worked_minutes');
        $totalEarnings = (float) $rows->sum('earnings_amount');

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'from_formatted' => $from->format('d.m.Y'),
            'to_formatted' => $to->format('d.m.Y'),
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60, 2),
            'total_earnings' => round($totalEarnings, 2),
            'total_earnings_formatted' => number_format($totalEarnings, 2, ',', '.').' ₺',
            'sessions' => $rows->count(),
            'hourly_sessions' => $rows->where('pricing_model', 'hourly')->count(),
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, summary: array<string, mixed>, from: string, to: string}
     */
    public function courierReport(Courier $courier, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfMonth();

        $rows = BusinessShiftAttendance::query()
            ->with(['shift', 'business'])
            ->where('courier_id', $courier->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('work_date')
            ->orderByDesc('started_at')
            ->get()
            ->map(fn (BusinessShiftAttendance $attendance) => $this->presenter->row($attendance))
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'summary' => $this->earningsSummary($courier, $from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }

    /**
     * Canlı Operasyon: o gün vardiyası olan tüm kuryeler (işletme filtresi opsiyonel).
     *
     * Sıra: girmemiş → geç başlayan → aktif → 1 saat kalan → henüz saati gelmeyen → tamamlanan
     *
     * @return array{
     *     work_date: string,
     *     work_date_formatted: string,
     *     is_future: bool,
     *     cards: list<array<string, mixed>>,
     *     totals: array<string, int>
     * }
     */
    public function liveOperations(?Carbon $day = null, ?int $businessId = null): array
    {
        $day ??= Carbon::today();
        $now = now();
        $date = $day->toDateString();
        $isFuture = ShiftAttendanceRules::isFutureDay($day, $now);

        $shiftsQuery = BusinessShift::query()
            ->with(['business.city', 'business.district', 'rosterCouriers'])
            ->where('is_active', true)
            ->when($businessId !== null, fn ($q) => $q->where('business_id', $businessId))
            ->orderBy('start_time');

        $shifts = $shiftsQuery->get()
            ->filter(fn (BusinessShift $shift) => $shift->runsOn($day))
            ->values();

        $jokers = BusinessShiftJokerAssignment::query()
            ->with(['absentCourier', 'jokerCourier'])
            ->whereIn('business_shift_id', $shifts->pluck('id'))
            ->whereDate('work_date', $date)
            ->get()
            ->groupBy('business_shift_id');

        $attendances = BusinessShiftAttendance::query()
            ->whereDate('work_date', $date)
            ->whereIn('business_shift_id', $shifts->pluck('id'))
            ->whereIn('status', ['in_progress', 'completed'])
            ->get()
            ->groupBy(fn (BusinessShiftAttendance $row) => $row->business_shift_id.'-'.$row->courier_id);

        $buckets = [
            'not_started' => [],
            'late_start' => [],
            'active' => [],
            'starting_soon' => [],
            'upcoming' => [],
            'completed' => [],
        ];

        foreach ($shifts as $shift) {
            $shiftStart = ShiftAttendanceRules::shiftStartAt($shift, $day);
            $shiftJokers = $jokers->get($shift->id, collect());
            $absentIds = $shiftJokers->pluck('absent_courier_id')->map(fn ($id) => (int) $id)->all();

            $working = $shift->rosterCouriers
                ->reject(fn (Courier $courier) => in_array((int) $courier->id, $absentIds, true))
                ->values();

            $workingRows = $working->map(fn (Courier $courier) => [
                'courier' => $courier,
                'is_joker' => false,
                'covers' => null,
            ])->all();

            foreach ($shiftJokers as $joker) {
                if ($joker->jokerCourier === null) {
                    continue;
                }
                $workingRows[] = [
                    'courier' => $joker->jokerCourier,
                    'is_joker' => true,
                    'covers' => $joker->absentCourier?->full_name,
                ];
            }

            foreach ($workingRows as $row) {
                /** @var Courier $courier */
                $courier = $row['courier'];
                $key = $shift->id.'-'.$courier->id;
                /** @var BusinessShiftAttendance|null $attendance */
                $attendance = $attendances->get($key)?->first();

                $bucket = $this->liveOpsBucket($attendance, $shiftStart, $now, $isFuture);
                $avatar = CourierAvatar::forCourier($courier);
                $lateMinutes = null;
                if ($attendance?->started_at !== null && $attendance->started_at->gt($shiftStart)) {
                    $lateMinutes = (int) $shiftStart->diffInMinutes($attendance->started_at, absolute: true);
                }

                $business = $shift->business;
                $cityName = $business?->city?->name;
                $districtName = $business?->district?->name;
                $location = collect([$cityName, $districtName])->filter()->implode(' / ');
                $businessName = $business?->displayName() ?? '—';

                $buckets[$bucket][] = [
                    'courier_id' => $courier->id,
                    'courier_name' => $courier->full_name,
                    'phone' => $courier->phone ?? '—',
                    'photo_url' => filled($courier->photo_path) ? Storage::disk('public')->url($courier->photo_path) : null,
                    'avatar_initials' => $avatar['avatar_initials'],
                    'avatar_color' => $avatar['avatar_color'],
                    'business_id' => $shift->business_id,
                    'business_name' => $businessName,
                    'business_location' => $location !== '' ? $location : null,
                    'business_label' => $location !== ''
                        ? $businessName.' · '.$location
                        : $businessName,
                    'shift_id' => $shift->id,
                    'shift_name' => $shift->name,
                    'start_time' => substr((string) $shift->start_time, 0, 5),
                    'end_time' => substr((string) $shift->end_time, 0, 5),
                    'time_range' => substr((string) $shift->start_time, 0, 5).'–'.substr((string) $shift->end_time, 0, 5),
                    'shift_start_sort' => $shiftStart->timestamp,
                    'is_joker' => $row['is_joker'],
                    'covers' => $row['covers'],
                    'bucket' => $bucket,
                    'bucket_label' => match ($bucket) {
                        'not_started' => 'Girmedi',
                        'late_start' => $lateMinutes !== null
                            ? 'Geç - '.$lateMinutes.' dk'
                            : 'Geç',
                        'active' => 'Aktif',
                        'starting_soon' => '1 saat kaldı',
                        'upcoming' => 'Bekliyor',
                        'completed' => 'Geldi',
                        default => $bucket,
                    },
                    'late_minutes' => $lateMinutes,
                    'attendance' => $attendance ? $this->presenter->row($attendance) : null,
                    'can_start' => $attendance === null && ! $isFuture,
                    'can_end' => $attendance?->isInProgress() ?? false,
                ];
            }
        }

        $order = ['not_started', 'late_start', 'active', 'starting_soon', 'upcoming', 'completed'];
        $cards = [];
        foreach ($order as $key) {
            usort($buckets[$key], function (array $a, array $b): int {
                return $a['shift_start_sort'] <=> $b['shift_start_sort']
                    ?: strcmp($a['courier_name'], $b['courier_name']);
            });
            array_push($cards, ...$buckets[$key]);
        }

        return [
            'work_date' => $date,
            'work_date_formatted' => $day->format('d.m.Y'),
            'is_future' => $isFuture,
            'cards' => $cards,
            'totals' => [
                'all' => count($cards),
                'not_started' => count($buckets['not_started']),
                'late_start' => count($buckets['late_start']),
                'active' => count($buckets['active']),
                'starting_soon' => count($buckets['starting_soon']),
                'upcoming' => count($buckets['upcoming']),
                'completed' => count($buckets['completed']),
            ],
        ];
    }

    private function liveOpsBucket(
        ?BusinessShiftAttendance $attendance,
        Carbon $shiftStart,
        Carbon $now,
        bool $isFuture,
    ): string {
        if ($attendance?->isInProgress()) {
            if ($attendance->started_at !== null && $attendance->started_at->gt($shiftStart)) {
                return 'late_start';
            }

            return 'active';
        }

        if ($attendance?->isCompleted()) {
            if ($attendance->started_at !== null && $attendance->started_at->gt($shiftStart)) {
                return 'late_start';
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

    /**
     * @return array{
     *     work_date: string,
     *     work_date_formatted: string,
     *     business_id: int,
     *     is_future: bool,
     *     shifts: list<array<string, mixed>>,
     *     totals: array{expected: int, planned: int, in_progress: int, completed: int, missing: int}
     * }
     */
    public function dayBoard(int $businessId, ?Carbon $day = null): array
    {
        $day ??= Carbon::today();
        $date = $day->toDateString();
        $isFuture = ShiftAttendanceRules::isFutureDay($day);

        $shifts = BusinessShift::query()
            ->with(['business.activePricing.pricingModelType', 'rosterCouriers'])
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->filter(fn (BusinessShift $shift) => $shift->runsOn($day))
            ->values();

        $jokers = BusinessShiftJokerAssignment::query()
            ->with(['absentCourier', 'jokerCourier'])
            ->whereIn('business_shift_id', $shifts->pluck('id'))
            ->whereDate('work_date', $date)
            ->get()
            ->groupBy('business_shift_id');

        $attendances = BusinessShiftAttendance::query()
            ->where('business_id', $businessId)
            ->whereDate('work_date', $date)
            ->whereIn('status', ['in_progress', 'completed'])
            ->get()
            ->groupBy(fn (BusinessShiftAttendance $row) => $row->business_shift_id.'-'.$row->courier_id);

        $totals = ['expected' => 0, 'planned' => 0, 'in_progress' => 0, 'completed' => 0, 'missing' => 0];
        $shiftRows = [];

        foreach ($shifts as $shift) {
            $shiftJokers = $jokers->get($shift->id, collect());
            $absentIds = $shiftJokers->pluck('absent_courier_id')->map(fn ($id) => (int) $id)->all();

            $working = $shift->rosterCouriers
                ->reject(fn (Courier $courier) => in_array((int) $courier->id, $absentIds, true))
                ->map(fn (Courier $courier) => [
                    'id' => (int) $courier->id,
                    'name' => $courier->full_name,
                    'is_joker' => false,
                    'covers' => null,
                ])
                ->values()
                ->all();

            foreach ($shiftJokers as $joker) {
                $working[] = [
                    'id' => (int) $joker->joker_courier_id,
                    'name' => $joker->jokerCourier?->full_name ?? '—',
                    'is_joker' => true,
                    'covers' => $joker->absentCourier?->full_name,
                ];
            }

            $couriers = [];
            foreach ($working as $courierRow) {
                $key = $shift->id.'-'.$courierRow['id'];
                /** @var BusinessShiftAttendance|null $attendance */
                $attendance = $attendances->get($key)?->first();

                $status = ShiftAttendanceRules::participationStatus(
                    $attendance?->status,
                    $day,
                );

                $totals['expected']++;
                $totals[$status]++;

                $canStart = $attendance === null
                    && ! $isFuture
                    && ShiftAttendanceRules::isWithinCourierStartWindow($shift, $day);

                // Personel, bugün/geçmişte pencere dışında da başlatabilir.
                $staffCanStart = $attendance === null && ! $isFuture;

                $couriers[] = [
                    'courier_id' => $courierRow['id'],
                    'courier_name' => $courierRow['name'],
                    'is_joker' => $courierRow['is_joker'],
                    'covers' => $courierRow['covers'],
                    'status' => $status,
                    'status_label' => ShiftAttendanceRules::statusLabel($status),
                    'attendance' => $attendance ? $this->presenter->row($attendance) : null,
                    'can_start' => $staffCanStart,
                    'courier_can_start' => $canStart,
                    'can_end' => $attendance?->isInProgress() ?? false,
                ];
            }

            $completed = collect($couriers)->where('status', 'completed')->count();
            $inProgress = collect($couriers)->where('status', 'in_progress')->count();
            $missing = collect($couriers)->where('status', 'missing')->count();
            $planned = collect($couriers)->where('status', 'planned')->count();
            $expected = count($couriers);

            $shiftRows[] = [
                'shift_id' => $shift->id,
                'shift_name' => $shift->name,
                'start_time' => substr((string) $shift->start_time, 0, 5),
                'end_time' => substr((string) $shift->end_time, 0, 5),
                'time_range' => substr((string) $shift->start_time, 0, 5).'–'.substr((string) $shift->end_time, 0, 5),
                'expected' => $expected,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'missing' => $missing,
                'planned' => $planned,
                'summary_label' => $this->boardSummaryLabel($expected, $completed, $inProgress, $missing, $planned, $isFuture),
                'couriers' => $couriers,
            ];
        }

        return [
            'work_date' => $date,
            'work_date_formatted' => $day->format('d.m.Y'),
            'business_id' => $businessId,
            'is_future' => $isFuture,
            'shifts' => $shiftRows,
            'totals' => $totals,
        ];
    }

    /**
     * @param  list<int>  $shiftIds
     * @return array<string, array{expected: int, started: int, in_progress: int, completed: int, missing: int, planned: int, label: string, is_future: bool}>
     */
    public function weekOccurrenceSummaries(int $businessId, array $shiftIds, string $from, string $to): array
    {
        if ($shiftIds === []) {
            return [];
        }

        $shifts = BusinessShift::query()
            ->with('rosterCouriers')
            ->where('business_id', $businessId)
            ->whereIn('id', $shiftIds)
            ->get()
            ->keyBy('id');

        $jokers = BusinessShiftJokerAssignment::query()
            ->whereIn('business_shift_id', $shiftIds)
            ->whereBetween('work_date', [$from, $to])
            ->get()
            ->groupBy(fn (BusinessShiftJokerAssignment $row) => $row->business_shift_id.'|'.$row->work_date->toDateString());

        $attendances = BusinessShiftAttendance::query()
            ->where('business_id', $businessId)
            ->whereIn('business_shift_id', $shiftIds)
            ->whereBetween('work_date', [$from, $to])
            ->whereIn('status', ['in_progress', 'completed'])
            ->get()
            ->groupBy(fn (BusinessShiftAttendance $row) => $row->business_shift_id.'|'.$row->work_date->toDateString().'|'.$row->courier_id);

        $summaries = [];
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $isFuture = ShiftAttendanceRules::isFutureDay($cursor);

            foreach ($shifts as $shift) {
                if (! $shift->runsOn($cursor)) {
                    continue;
                }

                $dayJokers = $jokers->get($shift->id.'|'.$date, collect());
                $absentIds = $dayJokers->pluck('absent_courier_id')->map(fn ($id) => (int) $id)->all();
                $workingIds = $shift->rosterCouriers
                    ->reject(fn (Courier $courier) => in_array((int) $courier->id, $absentIds, true))
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                foreach ($dayJokers as $joker) {
                    $workingIds[] = (int) $joker->joker_courier_id;
                }

                $workingIds = array_values(array_unique($workingIds));
                $expected = count($workingIds);
                $inProgress = 0;
                $completed = 0;

                foreach ($workingIds as $courierId) {
                    $attendance = $attendances->get($shift->id.'|'.$date.'|'.$courierId)?->first();
                    if ($attendance?->isInProgress()) {
                        $inProgress++;
                    } elseif ($attendance?->isCompleted()) {
                        $completed++;
                    }
                }

                $started = $inProgress + $completed;
                $missing = $isFuture ? 0 : max(0, $expected - $started);
                $planned = $isFuture ? max(0, $expected - $started) : 0;

                $summaries[$shift->id.'|'.$date] = [
                    'expected' => $expected,
                    'started' => $started,
                    'in_progress' => $inProgress,
                    'completed' => $completed,
                    'missing' => $missing,
                    'planned' => $planned,
                    'is_future' => $isFuture,
                    'label' => $expected === 0
                        ? 'Kadro boş'
                        : ($isFuture
                            ? sprintf('%d planlandı', $expected)
                            : sprintf('%d/%d geldi · %d gelmedi', $started, $expected, $missing)),
                ];
            }

            $cursor->addDay();
        }

        return $summaries;
    }

    /**
     * Vardiya bitiş + 30 dk geçmiş, hâlâ açık olanları otomatik sonlandır.
     *
     * @return int Sonlandırılan kayıt sayısı
     */
    public function autoEndOverdueAttendances(?Carbon $now = null): int
    {
        $now ??= now();
        $ended = 0;

        $open = BusinessShiftAttendance::query()
            ->with(['shift', 'business.activePricing.pricingModelType'])
            ->where('status', 'in_progress')
            ->get();

        foreach ($open as $attendance) {
            $shift = $attendance->shift;
            if ($shift === null || $attendance->work_date === null) {
                continue;
            }

            if (! ShiftAttendanceRules::shouldAutoEnd($shift, $attendance->work_date, $now)) {
                continue;
            }

            $this->completeAttendance(
                (int) $attendance->id,
                null,
                [
                    'auto_end' => true,
                    'ended_at' => ShiftAttendanceRules::shiftEndAt($shift, $attendance->work_date),
                    'notes_append' => 'Sistem otomatik sonlandırdı (bitiş + '.ShiftAttendanceRules::AUTO_END_GRACE_MINUTES.' dk).',
                ],
            );
            $ended++;
        }

        return $ended;
    }

    private function boardSummaryLabel(
        int $expected,
        int $completed,
        int $inProgress,
        int $missing,
        int $planned,
        bool $isFuture,
    ): string {
        if ($expected === 0) {
            return 'Kadro boş';
        }

        if ($isFuture) {
            return sprintf('%d planlandı', $planned);
        }

        return sprintf('%d/%d geldi · %d devam · %d gelmedi', $completed, $expected, $inProgress, $missing);
    }

    /**
     * @param  array{auto_end?: bool, ended_at?: Carbon|null, notes_append?: string|null}  $options
     */
    private function completeAttendance(int $attendanceId, ?int $expectedCourierId, array $options = []): BusinessShiftAttendance
    {
        return DB::transaction(function () use ($attendanceId, $expectedCourierId, $options): BusinessShiftAttendance {
            $attendance = BusinessShiftAttendance::query()
                ->with(['business.activePricing.pricingModelType', 'shift'])
                ->find($attendanceId);

            if ($attendance === null) {
                abort(404);
            }

            if ($expectedCourierId !== null && $attendance->courier_id !== $expectedCourierId) {
                abort(404);
            }

            if (! $attendance->isInProgress()) {
                throw ValidationException::withMessages([
                    'attendance' => 'Bu vardiya kaydı sonlandırılamaz.',
                ]);
            }

            $shift = $attendance->shift;
            $workDate = $attendance->work_date ?? Carbon::today();

            $endedAt = $options['ended_at'] ?? now();
            if (! $endedAt instanceof Carbon) {
                $endedAt = now();
            }

            // Hakediş süresi: yalnızca planlanan vardiya saati (erken/geç buffer yok).
            $minutes = $shift !== null
                ? ShiftAttendanceRules::scheduledMinutes($shift, $workDate)
                : max(1, (int) ($attendance->started_at ?? $endedAt)->diffInMinutes($endedAt, absolute: true));

            $hourlyRate = $attendance->hourly_rate !== null
                ? (float) $attendance->hourly_rate
                : null;

            if ($hourlyRate === null) {
                $contract = $attendance->commercial_contract_id
                    ? $this->commercialContracts->find((int) $attendance->commercial_contract_id)
                    : $this->commercialContracts->forBusinessOnDate(
                        (int) $attendance->business_id,
                        $workDate,
                    );
                $hourlyRate = $contract?->courierHourlyRateForAttendance();
                if ($attendance->pricing_model === null && $contract !== null) {
                    $attendance->pricing_model = $contract->work_type;
                }
                if ($attendance->commercial_contract_id === null && $contract !== null) {
                    $attendance->commercial_contract_id = $contract->id;
                }
            }

            $earnings = null;
            if ($hourlyRate !== null && $hourlyRate > 0) {
                $earnings = round(($minutes / 60) * $hourlyRate, 2);
            }

            $notes = trim((string) $attendance->notes);
            if (filled($options['notes_append'] ?? null)) {
                $notes = $notes !== ''
                    ? $notes."\n".$options['notes_append']
                    : (string) $options['notes_append'];
            }

            $attendance->update([
                'ended_at' => $endedAt,
                'status' => 'completed',
                'worked_minutes' => $minutes,
                'hourly_rate' => $hourlyRate,
                'earnings_amount' => $earnings,
                'pricing_model' => $attendance->pricing_model,
                'commercial_contract_id' => $attendance->commercial_contract_id,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            return $attendance->fresh(['shift', 'business', 'courier']);
        });
    }

    private function assertCourierCanAttend(Courier $courier, int $shiftId, Carbon $day): BusinessShift
    {
        $shift = BusinessShift::query()
            ->with(['business.activePricing.pricingModelType'])
            ->where('is_active', true)
            ->find($shiftId);

        if ($shift === null) {
            abort(404);
        }

        if (! $shift->runsOn($day)) {
            throw ValidationException::withMessages([
                'shift' => 'Bu vardiya seçilen günde çalışmıyor.',
            ]);
        }

        $isJoker = BusinessShiftJokerAssignment::query()
            ->where('business_shift_id', $shift->id)
            ->where('joker_courier_id', $courier->id)
            ->whereDate('work_date', $day->toDateString())
            ->exists();

        $isAbsent = BusinessShiftJokerAssignment::query()
            ->where('business_shift_id', $shift->id)
            ->where('absent_courier_id', $courier->id)
            ->whereDate('work_date', $day->toDateString())
            ->exists();

        $onRoster = DB::table('business_shift_couriers')
            ->where('business_shift_id', $shift->id)
            ->where('courier_id', $courier->id)
            ->exists();

        if ($isAbsent || (! $onRoster && ! $isJoker)) {
            throw ValidationException::withMessages([
                'shift' => 'Bu vardiyaya atanmış değilsiniz.',
            ]);
        }

        return $shift;
    }
}
