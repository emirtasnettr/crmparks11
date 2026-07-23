<?php

namespace App\Modules\ShiftPlanning\Services;

use App\Models\User;
use App\Modules\Business\Services\BusinessCommercialContractService;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Support\CourierAvatar;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Support\ShiftAttendanceRules;
use App\Support\GeoDistance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ShiftAttendanceService
{
    public function __construct(
        private readonly ShiftAttendancePresenter $presenter,
        private readonly BusinessCommercialContractService $commercialContracts,
        private readonly AttendanceEarningSyncService $earningSync,
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
     * @return array{
     *     today: list<array<string, mixed>>,
     *     upcoming: list<array<string, mixed>>,
     *     recent: list<array<string, mixed>>,
     *     summary: array<string, mixed>
     * }
     */
    public function portalPayload(Courier $courier, ?Carbon $day = null): array
    {
        $day ??= Carbon::today();

        return [
            'today' => $this->todayShiftsForCourier($courier, $day)
                ->values()
                ->all(),
            'upcoming' => $this->upcomingShiftsForCourier($courier, $day, 7)
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
        $shiftIds = DB::table('business_shift_couriers')
            ->where('courier_id', $courier->id)
            ->pluck('business_shift_id');

        if ($shiftIds->isEmpty()) {
            return collect();
        }

        $shifts = BusinessShift::query()
            ->with(['business'])
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

        return $shifts->map(function (BusinessShift $shift) use ($day, $attendances) {
            return $this->portalShiftRow($shift, $day, $attendances->get($shift->id), actionable: true);
        });
    }

    /**
     * Yaklaşan vardiya oluşumları (bugünden sonraki günler), en fazla $limit adet.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function upcomingShiftsForCourier(Courier $courier, ?Carbon $fromDay = null, int $limit = 7): Collection
    {
        $fromDay ??= Carbon::today();
        $limit = max(1, $limit);

        $shiftIds = DB::table('business_shift_couriers')
            ->where('courier_id', $courier->id)
            ->pluck('business_shift_id');

        if ($shiftIds->isEmpty()) {
            return collect();
        }

        $shifts = BusinessShift::query()
            ->with(['business'])
            ->whereIn('id', $shiftIds)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($shifts->isEmpty()) {
            return collect();
        }

        $upcoming = collect();
        $cursor = $fromDay->copy()->startOfDay()->addDay();
        $maxScanDays = 90;

        for ($i = 0; $i < $maxScanDays && $upcoming->count() < $limit; $i++) {
            $day = $cursor->copy()->addDays($i);

            foreach ($shifts as $shift) {
                if (! $shift->runsOn($day)) {
                    continue;
                }

                $upcoming->push($this->portalShiftRow($shift, $day, null, actionable: false));

                if ($upcoming->count() >= $limit) {
                    break;
                }
            }
        }

        return $upcoming
            ->sortBy(fn (array $row) => $row['work_date'].' '.$row['start_time'])
            ->values()
            ->take($limit);
    }

    /**
     * @return array<string, mixed>
     */
    private function portalShiftRow(
        BusinessShift $shift,
        Carbon $day,
        ?BusinessShiftAttendance $attendance,
        bool $actionable,
    ): array {
        $contract = $this->commercialContracts->forBusinessOnDate((int) $shift->business_id, $day);
        $pricingCode = $contract?->work_type;
        $withinStart = $actionable && ShiftAttendanceRules::isWithinCourierStartWindow($shift, $day);
        $inProgress = $attendance?->isInProgress() ?? false;
        $canEnd = $actionable && $inProgress && ShiftAttendanceRules::isCourierAllowedToEnd($shift, $day);
        $shiftEnd = ShiftAttendanceRules::shiftEndAt($shift, $day);
        $requiresEndReason = $canEnd && now()->lt($shiftEnd);
        $workTypes = \App\Modules\Business\Data\BusinessCommercialContractFormData::workTypes();
        $hasLocation = $shift->business?->latitude !== null && $shift->business?->longitude !== null;

        return [
            'shift_id' => $shift->id,
            'business_id' => $shift->business_id,
            'business_name' => $shift->business?->displayName() ?? '—',
            'start_time' => substr((string) $shift->start_time, 0, 5),
            'end_time' => substr((string) $shift->end_time, 0, 5),
            'time_range' => $shift->timeRangeLabel(),
            'work_date' => $day->toDateString(),
            'work_date_formatted' => $day->copy()->locale('tr')->translatedFormat('d M Y, l'),
            'pricing_model' => $pricingCode,
            'pricing_model_label' => $pricingCode ? ($workTypes[$pricingCode] ?? $pricingCode) : '—',
            'hourly_rate' => $contract?->courierHourlyRateForAttendance(),
            'attendance' => $attendance ? $this->presenter->row($attendance) : null,
            'has_location' => $hasLocation,
            'can_start' => $actionable && $attendance === null && $withinStart && $hasLocation,
            'can_end' => $canEnd,
            'requires_end_reason' => $requiresEndReason,
            'end_reasons' => ShiftAttendanceRules::endReasonLabels(),
            'waiting_for_end' => false,
            'start_window_opens_at' => ShiftAttendanceRules::earliestStartAt($shift, $day)->format('H:i'),
            'end_available_at' => $shiftEnd->format('H:i'),
            'location_blocked' => $actionable && $attendance === null && $withinStart && ! $hasLocation,
        ];
    }

    /**
     * @param  array{staff_assist?: bool, notes?: string|null, started_at?: Carbon|null, replaces_attendance_id?: int|null}  $options
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

            $geo = $this->assertCourierProximity($shift, $staffAssist, $options);

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
                'replaces_attendance_id' => isset($options['replaces_attendance_id'])
                    ? (int) $options['replaces_attendance_id']
                    : null,
                'start_latitude' => $geo['latitude'],
                'start_longitude' => $geo['longitude'],
                'start_accuracy_meters' => $geo['accuracy_meters'],
                'start_distance_meters' => $geo['distance_meters'],
            ]);
        });
    }

    /**
     * @param  array{latitude?: mixed, longitude?: mixed, accuracy?: mixed}  $options
     * @return array{latitude: float|null, longitude: float|null, accuracy_meters: int|null, distance_meters: int|null}
     */
    private function assertCourierProximity(BusinessShift $shift, bool $staffAssist, array $options): array
    {
        $empty = [
            'latitude' => null,
            'longitude' => null,
            'accuracy_meters' => null,
            'distance_meters' => null,
        ];

        if ($staffAssist) {
            return $empty;
        }

        $shift->loadMissing('business');
        $business = $shift->business;

        if ($business === null || $business->latitude === null || $business->longitude === null) {
            throw ValidationException::withMessages([
                'shift' => 'Bu işletmenin konumu tanımlı değil. Vardiya başlatılamaz. Yönetimle iletişime geçin.',
            ]);
        }

        if (! isset($options['latitude'], $options['longitude'])) {
            throw ValidationException::withMessages([
                'location' => 'Vardiya başlatmak için konum izni gereklidir.',
            ]);
        }

        $courierLat = (float) $options['latitude'];
        $courierLng = (float) $options['longitude'];
        $accuracy = isset($options['accuracy']) ? (int) round((float) $options['accuracy']) : null;

        if ($courierLat < -90 || $courierLat > 90 || $courierLng < -180 || $courierLng > 180) {
            throw ValidationException::withMessages([
                'location' => 'Geçersiz konum bilgisi alındı. Tekrar deneyin.',
            ]);
        }

        if ($accuracy !== null && $accuracy > ShiftAttendanceRules::START_MAX_ACCURACY_METERS) {
            throw ValidationException::withMessages([
                'location' => 'Konum doğruluğu yetersiz. Açık alanda tekrar deneyin.',
            ]);
        }

        $distance = GeoDistance::metersBetween(
            $courierLat,
            $courierLng,
            (float) $business->latitude,
            (float) $business->longitude,
        );
        $distanceMeters = (int) round($distance);

        if ($distanceMeters > ShiftAttendanceRules::START_PROXIMITY_METERS) {
            throw ValidationException::withMessages([
                'location' => 'İşletmeye en az '.ShiftAttendanceRules::START_PROXIMITY_METERS.' metre yakın olmalısınız. Şu an yaklaşık '.$distanceMeters.' metre uzaktasınız.',
            ]);
        }

        return [
            'latitude' => $courierLat,
            'longitude' => $courierLng,
            'accuracy_meters' => $accuracy,
            'distance_meters' => $distanceMeters,
        ];
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

    /**
     * Personel: girmeyen kuryeyi planlanan süreyle tamamlanmış (Geldi) olarak işaretler.
     */
    public function markAttendedForCourier(Courier $courier, int $shiftId, Carbon $day, User $staff, ?string $note = null): BusinessShiftAttendance
    {
        if (ShiftAttendanceRules::isFutureDay($day)) {
            throw ValidationException::withMessages([
                'shift' => 'Gelecek gün vardiyası geldi olarak işaretlenemez.',
            ]);
        }

        $shift = $this->assertCourierCanAttend($courier, $shiftId, $day);

        $exists = BusinessShiftAttendance::query()
            ->where('courier_id', $courier->id)
            ->where('business_shift_id', $shift->id)
            ->whereDate('work_date', $day->toDateString())
            ->whereIn('status', ['in_progress', 'completed'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'shift' => 'Bu vardiya için seçilen günde zaten kayıt var.',
            ]);
        }

        $staffNote = 'Personel müdahalesi: '.$staff->name.' geldi olarak işaretledi';
        if (filled($note)) {
            $staffNote .= ' — '.$note;
        }

        $created = $this->createRetrospectiveCompletedAttendance(
            $shift,
            (int) $courier->id,
            $day->copy(),
            $staffNote,
        );

        if (! $created) {
            throw ValidationException::withMessages([
                'shift' => 'Bu vardiya için seçilen günde zaten kayıt var.',
            ]);
        }

        $attendance = BusinessShiftAttendance::query()
            ->where('courier_id', $courier->id)
            ->where('business_shift_id', $shift->id)
            ->whereDate('work_date', $day->toDateString())
            ->where('status', 'completed')
            ->latest('id')
            ->firstOrFail();

        try {
            $this->earningSync->sync(
                $staff,
                [
                    'business_id' => (int) $shift->business_id,
                    'courier_id' => (int) $courier->id,
                    'period_year' => (int) $day->format('Y'),
                    'period_month' => (int) $day->format('n'),
                ],
            );
        } catch (Throwable $e) {
            Log::warning('Staff mark-attended earning sync failed', [
                'attendance_id' => $attendance->id,
                'message' => $e->getMessage(),
            ]);
        }

        return $attendance->fresh(['shift', 'business', 'courier']);
    }

    public function end(Courier $courier, int $attendanceId, array $options = []): BusinessShiftAttendance
    {
        return $this->completeAttendance($attendanceId, $courier->id, $options);
    }

    /**
     * Personel: devam eden vardiyayı bitiş saati seçerek sonlandırır; isteğe bağlı yerine kurye başlatır.
     *
     * @param  array{
     *     ended_at: Carbon,
     *     end_reason?: string|null,
     *     replacement_courier_id?: int|null,
     *     package_count?: int|null,
     *     notes?: string|null
     * }  $payload
     * @return array{ended: BusinessShiftAttendance, replacement: BusinessShiftAttendance|null}
     */
    public function endForCourier(int $attendanceId, User $staff, array $payload): array
    {
        $endedAt = $payload['ended_at'] ?? null;
        if (! $endedAt instanceof Carbon) {
            throw ValidationException::withMessages([
                'ended_at' => 'Bitiş saati zorunludur.',
            ]);
        }

        $endReason = filled($payload['end_reason'] ?? null) ? (string) $payload['end_reason'] : null;
        $replacementCourierId = isset($payload['replacement_courier_id']) && $payload['replacement_courier_id'] !== ''
            ? (int) $payload['replacement_courier_id']
            : null;
        $packageCount = array_key_exists('package_count', $payload) && $payload['package_count'] !== null && $payload['package_count'] !== ''
            ? (int) $payload['package_count']
            : null;
        $note = filled($payload['notes'] ?? null) ? (string) $payload['notes'] : null;

        if ($endReason !== null && ! in_array($endReason, ShiftAttendanceRules::endReasonCodes(), true)) {
            throw ValidationException::withMessages([
                'end_reason' => 'Geçersiz bitiş sebebi.',
            ]);
        }

        $attendance = BusinessShiftAttendance::query()
            ->with('shift')
            ->find($attendanceId);

        if ($attendance === null || ! $attendance->isInProgress()) {
            throw ValidationException::withMessages([
                'attendance' => 'Bu vardiya kaydı sonlandırılamaz.',
            ]);
        }

        $shift = $attendance->shift;
        $workDate = $attendance->work_date ?? Carbon::today();

        if ($shift === null) {
            throw ValidationException::withMessages([
                'attendance' => 'Bu vardiya kaydı sonlandırılamaz.',
            ]);
        }

        $shiftStart = ShiftAttendanceRules::shiftStartAt($shift, $workDate);
        $shiftEnd = ShiftAttendanceRules::shiftEndAt($shift, $workDate);
        $startedAt = $attendance->started_at?->copy() ?? $shiftStart->copy();

        if ($endedAt->lt($startedAt)) {
            throw ValidationException::withMessages([
                'ended_at' => 'Bitiş saati başlangıçtan önce olamaz.',
            ]);
        }

        if ($endedAt->lt($shiftStart)) {
            throw ValidationException::withMessages([
                'ended_at' => 'Bitiş saati vardiya başlangıcından önce olamaz.',
            ]);
        }

        if ($endedAt->gt($shiftEnd)) {
            $endedAt = $shiftEnd->copy();
        }

        $isEarlyEnd = $endedAt->lt($shiftEnd);

        if ($isEarlyEnd && $endReason === null) {
            throw ValidationException::withMessages([
                'end_reason' => 'Erken bitişte sebep seçilmelidir.',
            ]);
        }

        if ($replacementCourierId !== null) {
            if ($endReason === null) {
                throw ValidationException::withMessages([
                    'end_reason' => 'Yerine kurye eklerken sebep zorunludur.',
                ]);
            }

            if ($replacementCourierId === (int) $attendance->courier_id) {
                throw ValidationException::withMessages([
                    'replacement_courier_id' => 'Yerine aynı kurye seçilemez.',
                ]);
            }

            if ($endedAt->gte($shiftEnd)) {
                throw ValidationException::withMessages([
                    'replacement_courier_id' => 'Vardiya bitişinde yerine kurye eklenemez.',
                ]);
            }
        }

        $reasonLabel = ShiftAttendanceRules::endReasonLabel($endReason);
        $staffNote = 'Personel müdahalesi: '.$staff->name.' sonlandırdı';
        if ($reasonLabel) {
            $staffNote .= ' — Sebep: '.$reasonLabel;
        }
        if (filled($note)) {
            $staffNote .= ' — '.$note;
        }

        $options = [
            'staff_assist' => true,
            'ended_at' => $endedAt,
            'end_reason' => $endReason,
            'notes_append' => $staffNote,
            'use_billable_minutes' => true,
        ];

        if ($packageCount !== null) {
            $options['package_count'] = $packageCount;
        }

        $ended = $this->completeAttendance($attendanceId, null, $options);

        $replacement = null;
        if ($replacementCourierId !== null) {
            $replacementCourier = Courier::query()->findOrFail($replacementCourierId);
            $this->ensureCourierOnRoster($shift, $replacementCourier);

            $handoffNote = 'Personel müdahalesi: '.$staff->name.' yerine ekledi'
                .' (önceki: #'.$ended->id
                .($reasonLabel ? ', sebep: '.$reasonLabel : '')
                .')';

            $replacement = $this->start($replacementCourier, (int) $shift->id, $workDate->copy(), [
                'staff_assist' => true,
                'started_at' => $endedAt->copy(),
                'notes' => $handoffNote,
                'replaces_attendance_id' => $ended->id,
            ]);

            $ended->update(['replaced_by_attendance_id' => $replacement->id]);
            $ended = $ended->fresh(['shift', 'business', 'courier']);
        }

        return [
            'ended' => $ended,
            'replacement' => $replacement,
        ];
    }

    private function ensureCourierOnRoster(BusinessShift $shift, Courier $courier): void
    {
        $exists = DB::table('business_shift_couriers')
            ->where('business_shift_id', $shift->id)
            ->where('courier_id', $courier->id)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('business_shift_couriers')->insert([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

        $byModel = $rows
            ->groupBy(fn (BusinessShiftAttendance $row) => (string) ($row->pricing_model ?: 'unknown'))
            ->map(function (Collection $group, string $model): array {
                $minutes = (int) $group->sum('worked_minutes');
                $earnings = round((float) $group->sum('earnings_amount'), 2);

                return [
                    'pricing_model' => $model,
                    'pricing_model_label' => match ($model) {
                        'hourly' => 'Saatlik',
                        'per_package' => 'Paket Başı',
                        default => $model,
                    },
                    'sessions' => $group->count(),
                    'total_minutes' => $minutes,
                    'total_hours' => round($minutes / 60, 2),
                    'total_earnings' => $earnings,
                    'total_earnings_formatted' => number_format($earnings, 2, ',', '.').' ₺',
                ];
            })
            ->values()
            ->all();

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
            'by_pricing_model' => $byModel,
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
     * Geçmişe dönük (veya bitiş saati geçmiş) vardiya günleri için kadrodaki
     * kuryelere otomatik "Geldi / tamamlandı" katılım kaydı oluşturur.
     *
     * @param  list<int>|null  $courierIds  null = tüm kadro
     */
    public function materializeRetrospectiveCompletions(BusinessShift $shift, ?array $courierIds = null): int
    {
        if (! $shift->is_active) {
            return 0;
        }

        $shift->loadMissing('rosterCouriers');

        $courierIds ??= $shift->rosterCouriers
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $courierIds = array_values(array_unique(array_map('intval', $courierIds)));

        if ($courierIds === []) {
            return 0;
        }

        $today = Carbon::today();
        $from = $shift->start_date?->copy()->startOfDay() ?? $today->copy();
        $to = $shift->end_date?->copy()->startOfDay() ?? $today->copy();

        if ($from->gt($to)) {
            return 0;
        }

        $now = now();
        $created = 0;

        for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) {
            if (! $shift->runsOn($cursor)) {
                continue;
            }

            // Henüz bitmemiş (bugün devam eden / gelecek) günleri atla.
            if (ShiftAttendanceRules::shiftEndAt($shift, $cursor)->gte($now)) {
                continue;
            }

            foreach ($courierIds as $courierId) {
                if ($this->createRetrospectiveCompletedAttendance($shift, $courierId, $cursor->copy())) {
                    $created++;
                }
            }
        }

        if ($created > 0) {
            try {
                $this->earningSync->sync(
                    auth()->user(),
                    ['business_id' => (int) $shift->business_id],
                );
            } catch (Throwable $e) {
                Log::warning('Retrospective attendance earning sync failed', [
                    'business_shift_id' => $shift->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $created;
    }

    private function createRetrospectiveCompletedAttendance(
        BusinessShift $shift,
        int $courierId,
        Carbon $day,
        ?string $notes = null,
    ): bool
    {
        $exists = BusinessShiftAttendance::query()
            ->where('courier_id', $courierId)
            ->where('business_shift_id', $shift->id)
            ->whereDate('work_date', $day->toDateString())
            ->whereIn('status', ['in_progress', 'completed'])
            ->exists();

        if ($exists) {
            return false;
        }

        // Aynı işletmede örtüşen başka vardiya için zaten katılım varsa
        // ikinci retrospektif kayıt oluşturma (çift satır / çift hakediş).
        if ($this->courierHasOverlappingAttendance($courierId, (int) $shift->business_id, $day, $shift)) {
            return false;
        }

        $startedAt = ShiftAttendanceRules::shiftStartAt($shift, $day);
        $endedAt = ShiftAttendanceRules::shiftEndAt($shift, $day);
        $minutes = ShiftAttendanceRules::scheduledMinutes($shift, $day);

        $contract = $this->commercialContracts->forBusinessOnDate((int) $shift->business_id, $day);
        $pricingModel = $contract?->work_type;
        $packageCount = null;
        $hourlyRate = $contract?->courierHourlyRateForAttendance();
        $earnings = null;

        if ($pricingModel === 'per_package') {
            $packageCount = $contract?->guaranteedPackagesForMinutes($minutes);
            $unit = $contract !== null ? (float) $contract->courier_amount : 0.0;
            if ($packageCount !== null && $packageCount > 0 && $unit > 0) {
                $earnings = round($packageCount * $unit, 2);
            }
        } elseif ($hourlyRate !== null && $hourlyRate > 0) {
            $earnings = round(($minutes / 60) * $hourlyRate, 2);
        }

        BusinessShiftAttendance::query()->create([
            'business_shift_id' => $shift->id,
            'business_id' => $shift->business_id,
            'commercial_contract_id' => $contract?->id,
            'courier_id' => $courierId,
            'work_date' => $day->toDateString(),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'status' => 'completed',
            'worked_minutes' => $minutes,
            'package_count' => $packageCount,
            'hourly_rate' => $hourlyRate,
            'earnings_amount' => $earnings,
            'pricing_model' => $pricingModel,
            'notes' => $notes ?: 'Retrospektif vardiya — otomatik tamamlandı',
        ]);

        return true;
    }

    /**
     * Aynı kurye + işletme + günde, verilen vardiya penceresiyle örtüşen
     * aktif (in_progress/completed) katılım var mı?
     */
    private function courierHasOverlappingAttendance(
        int $courierId,
        int $businessId,
        Carbon $day,
        BusinessShift $shift,
        ?int $exceptAttendanceId = null,
    ): bool {
        $start = ShiftAttendanceRules::shiftStartAt($shift, $day);
        $end = ShiftAttendanceRules::shiftEndAt($shift, $day);

        $existing = BusinessShiftAttendance::query()
            ->with(['shift' => fn ($q) => $q->withTrashed()])
            ->where('courier_id', $courierId)
            ->where('business_id', $businessId)
            ->whereDate('work_date', $day->toDateString())
            ->whereIn('status', ['in_progress', 'completed'])
            ->when($exceptAttendanceId !== null, fn ($q) => $q->where('id', '!=', $exceptAttendanceId))
            ->get();

        foreach ($existing as $attendance) {
            if ($this->attendanceWindowOverlaps($attendance, $start, $end, $day)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Katılım penceresi (önce started_at/ended_at, yoksa soft-delete dahil vardiya).
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function attendanceWindow(BusinessShiftAttendance $attendance, Carbon $day): ?array
    {
        if ($attendance->started_at !== null && $attendance->ended_at !== null) {
            return [
                Carbon::parse($attendance->started_at),
                Carbon::parse($attendance->ended_at),
            ];
        }

        $shift = $attendance->relationLoaded('shift')
            ? $attendance->shift
            : null;

        if ($shift === null) {
            $shift = BusinessShift::withTrashed()->find($attendance->business_shift_id);
        }

        if ($shift === null) {
            return null;
        }

        return [
            ShiftAttendanceRules::shiftStartAt($shift, $day),
            ShiftAttendanceRules::shiftEndAt($shift, $day),
        ];
    }

    private function attendanceWindowOverlaps(
        BusinessShiftAttendance $attendance,
        Carbon $start,
        Carbon $end,
        Carbon $day,
    ): bool {
        $window = $this->attendanceWindow($attendance, $day);
        if ($window === null) {
            return false;
        }

        [$otherStart, $otherEnd] = $window;

        return $start->lt($otherEnd) && $otherStart->lt($end);
    }

    private function attendancesOverlapOnDay(
        BusinessShiftAttendance $a,
        BusinessShiftAttendance $b,
        Carbon $day,
    ): bool {
        $windowA = $this->attendanceWindow($a, $day);
        $windowB = $this->attendanceWindow($b, $day);

        if ($windowA === null || $windowB === null) {
            return false;
        }

        [$startA, $endA] = $windowA;
        [$startB, $endB] = $windowB;

        return $startA->lt($endB) && $startB->lt($endA);
    }

    /**
     * Aynı kurye + işletme + günde örtüşen vardiya katılımlarını tekilleştirir.
     * Tutulan kayıt: personel müdahalesi > gerçek (retrospektif olmayan) > yüksek tutar > düşük id.
     *
     * @return array{groups: int, removed: int, courier_ids: list<int>}
     */
    public function dedupeOverlappingAttendances(?int $courierId = null): array
    {
        $base = BusinessShiftAttendance::query()
            ->whereIn('status', ['in_progress', 'completed'])
            ->when($courierId !== null, fn ($q) => $q->where('courier_id', $courierId));

        $keys = (clone $base)
            ->select(['courier_id', 'business_id', 'work_date'])
            ->groupBy('courier_id', 'business_id', 'work_date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $removed = 0;
        $groups = 0;
        $affectedCourierIds = [];

        foreach ($keys as $key) {
            $rows = BusinessShiftAttendance::query()
                ->with(['shift' => fn ($q) => $q->withTrashed()])
                ->where('courier_id', $key->courier_id)
                ->where('business_id', $key->business_id)
                ->whereDate('work_date', $key->work_date)
                ->whereIn('status', ['in_progress', 'completed'])
                ->orderBy('id')
                ->get();

            if ($rows->count() < 2) {
                continue;
            }

            $day = Carbon::parse($key->work_date)->startOfDay();
            $toRemove = [];

            for ($i = 0; $i < $rows->count(); $i++) {
                if (isset($toRemove[$rows[$i]->id])) {
                    continue;
                }

                for ($j = $i + 1; $j < $rows->count(); $j++) {
                    if (isset($toRemove[$rows[$j]->id])) {
                        continue;
                    }

                    $a = $rows[$i];
                    $b = $rows[$j];

                    if (! $this->attendancesOverlapOnDay($a, $b, $day)) {
                        continue;
                    }

                    $keep = $this->preferAttendanceForDedupe($a, $b);
                    $drop = $keep->id === $a->id ? $b : $a;
                    $toRemove[$drop->id] = $drop;
                }
            }

            if ($toRemove === []) {
                continue;
            }

            $groups++;
            $affectedCourierIds[(int) $key->courier_id] = true;

            foreach ($toRemove as $duplicate) {
                $duplicate->delete();
                $removed++;
            }
        }

        if ($removed > 0) {
            try {
                $this->earningSync->sync(
                    auth()->user(),
                    $courierId !== null ? ['courier_id' => $courierId] : [],
                );
            } catch (Throwable $e) {
                Log::warning('Overlapping attendance dedupe earning sync failed', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'groups' => $groups,
            'removed' => $removed,
            'courier_ids' => array_map('intval', array_keys($affectedCourierIds)),
        ];
    }

    private function preferAttendanceForDedupe(
        BusinessShiftAttendance $a,
        BusinessShiftAttendance $b,
    ): BusinessShiftAttendance {
        $score = function (BusinessShiftAttendance $row): array {
            $notes = (string) ($row->notes ?? '');
            $isStaff = str_contains($notes, 'Personel müdahalesi');
            $isRetrospective = str_contains($notes, 'Retrospektif');

            return [
                $isStaff ? 1 : 0,
                $isRetrospective ? 0 : 1,
                (float) ($row->earnings_amount ?? 0),
                // Daha eski kayıt tercih (düşük id = yüksek skor için negatif)
                -1 * (int) $row->id,
            ];
        };

        return $score($a) >= $score($b) ? $a : $b;
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

            foreach ($shift->rosterCouriers as $courier) {
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
                $shiftEnd = ShiftAttendanceRules::shiftEndAt($shift, $day);
                $missing = $attendance === null && ! $isFuture;

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
                    'start_time' => substr((string) $shift->start_time, 0, 5),
                    'end_time' => substr((string) $shift->end_time, 0, 5),
                    'time_range' => $shift->timeRangeLabel(),
                    'shift_start_sort' => $shiftStart->timestamp,
                    'bucket' => $bucket,
                    'bucket_label' => match ($bucket) {
                        'not_started' => 'Girmedi',
                        'late_start' => $lateMinutes !== null
                            ? 'Geç - '.$lateMinutes.' dk'
                            : 'Geç',
                        'active' => 'Aktif',
                        'starting_soon' => 'Yaklaşan',
                        'upcoming' => 'Bekliyor',
                        'completed' => 'Geldi',
                        default => $bucket,
                    },
                    'late_minutes' => $lateMinutes,
                    'attendance' => $attendance ? $this->presenter->row($attendance) : null,
                    'can_start' => $missing && $now->lt($shiftEnd),
                    'can_mark_attended' => $missing && $now->gte($shiftEnd),
                    'can_end' => $attendance?->isInProgress() ?? false,
                    'shift_start_at' => $shiftStart->format('Y-m-d\TH:i'),
                    'shift_end_at' => $shiftEnd->format('Y-m-d\TH:i'),
                    'pricing_model' => $attendance?->pricing_model,
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

        $attendances = BusinessShiftAttendance::query()
            ->where('business_id', $businessId)
            ->whereDate('work_date', $date)
            ->whereIn('status', ['in_progress', 'completed'])
            ->get()
            ->groupBy(fn (BusinessShiftAttendance $row) => $row->business_shift_id.'-'.$row->courier_id);

        $totals = ['expected' => 0, 'planned' => 0, 'in_progress' => 0, 'completed' => 0, 'missing' => 0];
        $shiftRows = [];

        foreach ($shifts as $shift) {
            $working = $shift->rosterCouriers
                ->map(fn (Courier $courier) => [
                    'id' => (int) $courier->id,
                    'name' => $courier->full_name,
                ])
                ->values()
                ->all();

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

                // Personel, bugün/geçmişte pencere dışında da başlatabilir / geldi işaretleyebilir.
                $missing = $attendance === null && ! $isFuture;
                $shiftEnded = now()->gte(ShiftAttendanceRules::shiftEndAt($shift, $day));

                $couriers[] = [
                    'courier_id' => $courierRow['id'],
                    'courier_name' => $courierRow['name'],
                    'status' => $status,
                    'status_label' => ShiftAttendanceRules::statusLabel($status),
                    'attendance' => $attendance ? $this->presenter->row($attendance) : null,
                    'can_start' => $missing && ! $shiftEnded,
                    'can_mark_attended' => $missing && $shiftEnded,
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
                'start_time' => substr((string) $shift->start_time, 0, 5),
                'end_time' => substr((string) $shift->end_time, 0, 5),
                'time_range' => $shift->timeRangeLabel(),
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
     * @return array<string, array{
     *     required: int,
     *     assigned: int,
     *     expected: int,
     *     started: int,
     *     in_progress: int,
     *     completed: int,
     *     missing_assignments: int,
     *     assigned_not_started: int,
     *     missing: int,
     *     planned: int,
     *     label: string,
     *     is_future: bool,
     *     has_started: bool
     * }>
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

                $workingCouriers = $shift->rosterCouriers
                    ->map(fn (Courier $courier) => [
                        'id' => (int) $courier->id,
                        'name' => $courier->full_name,
                    ])
                    ->values()
                    ->all();

                $required = max(1, (int) $shift->required_headcount);
                $assigned = count($workingCouriers);
                $missingAssignments = max(0, $required - $assigned);
                $inProgress = 0;
                $completed = 0;
                $shiftStart = ShiftAttendanceRules::shiftStartAt($shift, $cursor);
                $shiftEnded = now()->gte(ShiftAttendanceRules::shiftEndAt($shift, $cursor));
                $shiftHasStarted = ! $isFuture && now()->gte($shiftStart);
                $courierRows = [];

                foreach ($workingCouriers as $courierRow) {
                    $courierId = $courierRow['id'];
                    $attendance = $attendances->get($shift->id.'|'.$date.'|'.$courierId)?->first();
                    if ($attendance?->isInProgress()) {
                        $inProgress++;
                    } elseif ($attendance?->isCompleted()) {
                        $completed++;
                    }

                    $missing = $attendance === null && ! $isFuture;
                    $courierRows[] = [
                        'id' => $courierId,
                        'name' => $courierRow['name'],
                        'attendance_id' => $attendance?->id,
                        'status' => $attendance?->status,
                        'pricing_model' => $attendance?->pricing_model,
                        'started_at' => $attendance?->started_at?->format('Y-m-d\TH:i'),
                        'shift_start_at' => $shiftStart->format('Y-m-d\TH:i'),
                        'shift_end_at' => ShiftAttendanceRules::shiftEndAt($shift, $cursor)->format('Y-m-d\TH:i'),
                        'can_start' => $missing && ! $shiftEnded,
                        'can_mark_attended' => $missing && $shiftEnded,
                        'can_end' => $attendance?->isInProgress() ?? false,
                    ];
                }

                $started = $inProgress + $completed;
                $assignedNotStarted = $shiftHasStarted ? max(0, $assigned - $started) : 0;
                $operationalShortage = $shiftHasStarted
                    ? max(0, $required - $started)
                    : $missingAssignments;
                $planned = $shiftHasStarted ? 0 : max(0, $assigned - $started);

                $summaries[$shift->id.'|'.$date] = [
                    'required' => $required,
                    'assigned' => $assigned,
                    'expected' => $required,
                    'started' => $started,
                    'in_progress' => $inProgress,
                    'completed' => $completed,
                    'missing_assignments' => $missingAssignments,
                    'assigned_not_started' => $assignedNotStarted,
                    'missing' => $operationalShortage,
                    'planned' => $planned,
                    'is_future' => $isFuture,
                    'has_started' => $shiftHasStarted,
                    'couriers' => $courierRows,
                    'label' => $this->occurrenceSummaryLabel(
                        $required,
                        $assigned,
                        $started,
                        $missingAssignments,
                        $assignedNotStarted,
                        $operationalShortage,
                        $shiftHasStarted,
                    ),
                ];
            }

            $cursor->addDay();
        }

        return $summaries;
    }

    private function occurrenceSummaryLabel(
        int $required,
        int $assigned,
        int $started,
        int $missingAssignments,
        int $assignedNotStarted,
        int $operationalShortage,
        bool $shiftHasStarted,
    ): string {
        if (! $shiftHasStarted) {
            if ($missingAssignments > 0) {
                return sprintf('%d/%d atandı · %d kişi eksik', $assigned, $required, $missingAssignments);
            }

            return sprintf('%d/%d atandı', $assigned, $required);
        }

        $parts = [sprintf('%d/%d geldi', $started, $required)];

        if ($operationalShortage > 0) {
            $parts[] = sprintf('%d eksik', $operationalShortage);
        }

        if ($assignedNotStarted > 0) {
            $parts[] = sprintf('%d katılmadı', $assignedNotStarted);
        }

        return implode(' · ', $parts);
    }

    /**
     * Vardiya bitiş + 15 dk geçmiş, hâlâ açık olanları otomatik sonlandır.
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
     * @param  array{
     *     auto_end?: bool,
     *     staff_assist?: bool,
     *     ended_at?: Carbon|null,
     *     end_reason?: string|null,
     *     notes_append?: string|null,
     *     package_count?: mixed,
     *     use_billable_minutes?: bool,
     *     latitude?: mixed,
     *     longitude?: mixed,
     *     accuracy?: mixed
     * }  $options
     */
    private function completeAttendance(int $attendanceId, ?int $expectedCourierId, array $options = []): BusinessShiftAttendance
    {
        $fresh = DB::transaction(function () use ($attendanceId, $expectedCourierId, $options): BusinessShiftAttendance {
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
            $staffAssist = (bool) ($options['staff_assist'] ?? false);
            $autoEnd = (bool) ($options['auto_end'] ?? false);

            if (! $staffAssist && ! $autoEnd) {
                if ($shift === null) {
                    throw ValidationException::withMessages([
                        'attendance' => 'Bu vardiya kaydı sonlandırılamaz.',
                    ]);
                }

                if (! ShiftAttendanceRules::isCourierAllowedToEnd($shift, $workDate)) {
                    throw ValidationException::withMessages([
                        'attendance' => 'Bu vardiya henüz sonlandırılamaz.',
                    ]);
                }
            }

            $endGeo = (! $staffAssist && ! $autoEnd)
                ? $this->captureEndLocation($options)
                : [
                    'latitude' => null,
                    'longitude' => null,
                    'accuracy_meters' => null,
                ];

            $endedAt = $options['ended_at'] ?? now();
            if (! $endedAt instanceof Carbon) {
                $endedAt = now();
            }

            $startedAt = $attendance->started_at?->copy() ?? $endedAt->copy();
            $useBillable = (bool) ($options['use_billable_minutes'] ?? true);

            if ($shift !== null && $useBillable) {
                $minutes = ShiftAttendanceRules::billableMinutes($shift, $workDate, $startedAt, $endedAt);
            } elseif ($shift !== null) {
                $minutes = ShiftAttendanceRules::scheduledMinutes($shift, $workDate);
            } else {
                $minutes = max(1, (int) $startedAt->diffInMinutes($endedAt, absolute: true));
            }

            $contract = $attendance->commercial_contract_id
                ? $this->commercialContracts->find((int) $attendance->commercial_contract_id)
                : $this->commercialContracts->forBusinessOnDate(
                    (int) $attendance->business_id,
                    $workDate,
                );

            if ($attendance->pricing_model === null && $contract !== null) {
                $attendance->pricing_model = $contract->work_type;
            }
            if ($attendance->commercial_contract_id === null && $contract !== null) {
                $attendance->commercial_contract_id = $contract->id;
            }

            $pricingModel = $attendance->pricing_model ?: $contract?->work_type;
            $isEarlyEnd = $shift !== null
                && ShiftAttendanceRules::isEarlyEnd($shift, $workDate, $endedAt);

            $endReason = array_key_exists('end_reason', $options)
                ? (filled($options['end_reason'] ?? null) ? (string) $options['end_reason'] : null)
                : $attendance->end_reason;

            if ($endReason !== null && ! in_array($endReason, ShiftAttendanceRules::endReasonCodes(), true)) {
                throw ValidationException::withMessages([
                    'end_reason' => 'Geçersiz bitiş sebebi.',
                ]);
            }

            if (! $staffAssist && ! $autoEnd && $isEarlyEnd && $endReason === null) {
                throw ValidationException::withMessages([
                    'end_reason' => 'Erken bitişte sebep seçilmelidir.',
                ]);
            }

            $packageCount = $this->resolvePackageCountOnComplete(
                $options,
                $pricingModel,
                $contract,
                $autoEnd,
                $staffAssist,
                $isEarlyEnd,
                $minutes,
            );

            $hourlyRate = $attendance->hourly_rate !== null
                ? (float) $attendance->hourly_rate
                : $contract?->courierHourlyRateForAttendance();

            $earnings = $this->resolveEarningsOnComplete(
                $pricingModel,
                $packageCount,
                $minutes,
                $hourlyRate,
                $contract,
            );

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
                'package_count' => $packageCount,
                'hourly_rate' => $hourlyRate,
                'earnings_amount' => $earnings,
                'pricing_model' => $pricingModel,
                'commercial_contract_id' => $attendance->commercial_contract_id,
                'notes' => $notes !== '' ? $notes : null,
                'end_reason' => $endReason,
                'end_latitude' => $endGeo['latitude'],
                'end_longitude' => $endGeo['longitude'],
                'end_accuracy_meters' => $endGeo['accuracy_meters'],
            ]);

            return $attendance->fresh(['shift', 'business', 'courier']);
        });

        $this->syncEarningsForCompletedAttendance($fresh);

        return $fresh;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function resolvePackageCountOnComplete(
        array $options,
        ?string $pricingModel,
        mixed $contract,
        bool $autoEnd,
        bool $staffAssist,
        bool $isEarlyEnd = false,
        int $minutes = 0,
    ): ?float {
        if ($pricingModel !== 'per_package') {
            return null;
        }

        $provided = array_key_exists('package_count', $options)
            && $options['package_count'] !== null
            && $options['package_count'] !== '';

        $entered = null;
        if ($provided) {
            $entered = (float) $options['package_count'];
            if ($entered < 0) {
                throw ValidationException::withMessages([
                    'package_count' => 'Paket sayısı negatif olamaz.',
                ]);
            }
        }

        $guaranteeFloor = $contract instanceof \App\Modules\Business\Models\BusinessCommercialContract
            ? $contract->guaranteedPackagesForMinutes($minutes)
            : null;

        // Kurye kendi bitirişinde fiili paket girmek zorunda (garanti olsa bile).
        if (! $provided && ! $autoEnd && ! $staffAssist) {
            throw ValidationException::withMessages([
                'package_count' => 'Paket başı vardiyada paket sayısı zorunludur.',
            ]);
        }

        // Personel erken bitiş: garanti yoksa fiili paket zorunlu.
        if (! $provided && $isEarlyEnd && $staffAssist && $guaranteeFloor === null) {
            throw ValidationException::withMessages([
                'package_count' => 'Erken bitişte paket sayısı zorunludur.',
            ]);
        }

        if ($provided && $entered < 1 && ! $autoEnd && $guaranteeFloor === null) {
            throw ValidationException::withMessages([
                'package_count' => 'Paket başı vardiyada paket sayısı zorunludur.',
            ]);
        }

        $billable = $contract instanceof \App\Modules\Business\Models\BusinessCommercialContract
            ? $contract->billablePackageCount($minutes, $entered)
            : $entered;

        if ($billable !== null && $billable > 0) {
            return round($billable, 2);
        }

        return $entered !== null ? round($entered, 2) : null;
    }

    private function resolveEarningsOnComplete(
        ?string $pricingModel,
        ?float $packageCount,
        int $minutes,
        ?float $hourlyRate,
        mixed $contract,
    ): ?float {
        if ($pricingModel === 'per_package') {
            $unit = $contract !== null ? (float) $contract->courier_amount : 0.0;
            if ($packageCount !== null && $packageCount > 0 && $unit > 0) {
                return round($packageCount * $unit, 2);
            }

            return null;
        }

        if ($hourlyRate !== null && $hourlyRate > 0) {
            return round(($minutes / 60) * $hourlyRate, 2);
        }

        return null;
    }

    private function syncEarningsForCompletedAttendance(BusinessShiftAttendance $attendance): void
    {
        try {
            $workDate = $attendance->work_date ?? $attendance->ended_at ?? now();

            $this->earningSync->sync(
                auth()->user(),
                [
                    'business_id' => (int) $attendance->business_id,
                    'courier_id' => (int) $attendance->courier_id,
                    'period_year' => (int) $workDate->format('Y'),
                    'period_month' => (int) $workDate->format('n'),
                ],
            );
        } catch (Throwable $e) {
            Log::warning('Attendance earning sync failed after completion', [
                'attendance_id' => $attendance->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sonlandırma konumu — yakınlık kuralı yok; istenen yerden sonlandırılabilir.
     *
     * @param  array{latitude?: mixed, longitude?: mixed, accuracy?: mixed}  $options
     * @return array{latitude: float, longitude: float, accuracy_meters: int|null}
     */
    private function captureEndLocation(array $options): array
    {
        if (! isset($options['latitude'], $options['longitude'])) {
            throw ValidationException::withMessages([
                'location' => 'Vardiya sonlandırmak için konum izni gereklidir.',
            ]);
        }

        $courierLat = (float) $options['latitude'];
        $courierLng = (float) $options['longitude'];
        $accuracy = isset($options['accuracy']) ? (int) round((float) $options['accuracy']) : null;

        if ($courierLat < -90 || $courierLat > 90 || $courierLng < -180 || $courierLng > 180) {
            throw ValidationException::withMessages([
                'location' => 'Geçersiz konum bilgisi alındı. Tekrar deneyin.',
            ]);
        }

        return [
            'latitude' => $courierLat,
            'longitude' => $courierLng,
            'accuracy_meters' => $accuracy,
        ];
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

        $onRoster = DB::table('business_shift_couriers')
            ->where('business_shift_id', $shift->id)
            ->where('courier_id', $courier->id)
            ->exists();

        if (! $onRoster) {
            throw ValidationException::withMessages([
                'shift' => 'Bu vardiyaya atanmış değilsiniz.',
            ]);
        }

        return $shift;
    }
}
