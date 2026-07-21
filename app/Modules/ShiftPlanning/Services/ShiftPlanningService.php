<?php

namespace App\Modules\ShiftPlanning\Services;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Data\ShiftPlanningFormData;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftCourier;
use App\Modules\ShiftPlanning\Support\ShiftCourierConflictChecker;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftPlanningService
{
    public function __construct(
        private readonly ShiftPlanningPresenter $presenter,
        private readonly ShiftCourierConflictChecker $conflicts,
        private readonly ShiftAttendanceService $attendances,
    ) {}

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function businesses(): array
    {
        return Business::query()
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->displayName(),
            ])
            ->all();
    }

    public function findBusiness(int $id): ?Business
    {
        return Business::query()->find($id);
    }

    /**
     * @return Collection<int, BusinessShift>
     */
    public function forBusiness(int $businessId): Collection
    {
        return BusinessShift::query()
            ->where('business_id', $businessId)
            ->with(['rosterCouriers'])
            ->orderBy('start_time')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?BusinessShift
    {
        return BusinessShift::query()
            ->with(['business', 'rosterCouriers'])
            ->find($id);
    }

    public function activeRosterCourierCount(int $businessId): int
    {
        return (int) BusinessShiftCourier::query()
            ->whereHas('shift', function ($query) use ($businessId): void {
                $query->where('business_id', $businessId)
                    ->where('is_active', true)
                    ->where(function ($inner): void {
                        $inner->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', now()->toDateString());
                    });
            })
            ->pluck('courier_id')
            ->unique()
            ->count();
    }

    /**
     * Vardiya kadrosu için tüm aktif kuryeler (işletme ataması zorunlu değil).
     *
     * @return array<int, array{id: int, name: string, phone: string}>
     */
    public function availableCouriers(int $businessId = 0): array
    {
        return Courier::query()
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'phone'])
            ->map(fn (Courier $courier) => [
                'id' => $courier->id,
                'name' => $courier->full_name,
                'phone' => $courier->phone ?? '—',
            ])
            ->all();
    }

    /**
     * Seçilen tarih/saat aralığında çakışması olmayan aktif kuryeler.
     *
     * @param  array{
     *     start_date?: mixed,
     *     end_date?: mixed,
     *     start_time?: mixed,
     *     end_time?: mixed,
     *     days_of_week?: mixed,
     *     excluded_dates?: mixed,
     * }  $schedule
     * @return array<int, array{id: int, name: string, phone: string}>
     */
    public function eligibleCouriersForSchedule(array $schedule, ?int $excludeShiftId = null): array
    {
        $couriers = $this->availableCouriers();
        if ($couriers === []) {
            return [];
        }

        $busy = array_flip($this->conflicts->busyCourierIds(
            array_map(fn (array $courier) => (int) $courier['id'], $couriers),
            $schedule,
            $excludeShiftId,
        ));

        return array_values(array_filter(
            $couriers,
            fn (array $courier) => ! isset($busy[(int) $courier['id']]),
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): BusinessShift
    {
        return DB::transaction(function () use ($data, $user): BusinessShift {
            $shift = BusinessShift::query()->create([
                'business_id' => $data['business_id'],
                'name' => $data['name'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'end_date' => $data['end_date'] ?? ($data['start_date'] ?? now()->toDateString()),
                'required_headcount' => max(1, (int) ($data['required_headcount'] ?? 1)),
                'notes' => $data['notes'] ?? null,
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : true,
                'created_by' => $user->id,
            ]);

            $courierIds = $this->normalizeCourierIds($data['courier_ids'] ?? []);
            if ($courierIds !== []) {
                $this->conflicts->assertNoRosterConflicts($courierIds, $this->schedulePayload($shift));
                $shift->rosterCouriers()->sync($courierIds);
            }

            $shift = $shift->fresh(['rosterCouriers']);
            $this->attendances->materializeRetrospectiveCompletions($shift);

            return $shift;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BusinessShift $shift, array $data): BusinessShift
    {
        return DB::transaction(function () use ($shift, $data) {
            $courierIds = array_key_exists('courier_ids', $data)
                ? $this->normalizeCourierIds($data['courier_ids'] ?? [])
                : $shift->rosterCouriers()->pluck('couriers.id')->map(fn ($id) => (int) $id)->all();

            if ($courierIds !== []) {
                $this->conflicts->assertNoRosterConflicts(
                    $courierIds,
                    [
                        'start_date' => $data['start_date'] ?? $shift->start_date,
                        'end_date' => $data['end_date'] ?? $shift->end_date,
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'days_of_week' => $shift->days_of_week,
                        'excluded_dates' => $shift->excluded_dates,
                    ],
                    $shift->id,
                    'start_time',
                );
            }

            $shift->update([
                'name' => $data['name'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'start_date' => $data['start_date'] ?? $shift->start_date,
                'end_date' => $data['end_date'] ?? $shift->end_date,
                'required_headcount' => max(1, (int) ($data['required_headcount'] ?? $shift->required_headcount)),
                'notes' => $data['notes'] ?? null,
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : $shift->is_active,
            ]);

            if (array_key_exists('courier_ids', $data)) {
                if (count($courierIds) > max(1, (int) $shift->required_headcount)) {
                    throw ValidationException::withMessages([
                        'courier_ids' => "Bu vardiyada en fazla {$shift->required_headcount} kişi çalışabilir.",
                    ]);
                }

                $shift->rosterCouriers()->sync($courierIds);
            }

            $shift = $shift->fresh(['rosterCouriers']);
            $this->attendances->materializeRetrospectiveCompletions($shift);

            return $shift;
        });
    }

    /**
     * @param  array<int, mixed>  $courierIds
     */
    public function syncRoster(BusinessShift $shift, array $courierIds): BusinessShift
    {
        $normalized = $this->normalizeCourierIds($courierIds);

        if (count($normalized) > max(1, (int) $shift->required_headcount)) {
            throw ValidationException::withMessages([
                'courier_ids' => "Bu vardiyada en fazla {$shift->required_headcount} kişi çalışabilir.",
            ]);
        }

        if ($normalized !== []) {
            $this->conflicts->assertNoRosterConflicts(
                $normalized,
                $this->schedulePayload($shift),
                $shift->id,
            );
        }

        $shift->rosterCouriers()->sync($normalized);

        $shift = $shift->fresh(['rosterCouriers']);
        $this->attendances->materializeRetrospectiveCompletions($shift);

        return $shift;
    }

    public function delete(BusinessShift $shift): void
    {
        DB::transaction(function () use ($shift): void {
            $shift->shiftCouriers()->delete();
            $shift->dayCouriers()->delete();
            $shift->delete();
        });
    }

    /**
     * @return array{week_start: string, week_end: string, label: string, days: array<int, array<string, mixed>>, prev_week: string, next_week: string, is_current: bool}
     */
    public function weekMeta(?string $weekStart = null): array
    {
        $start = $weekStart
            ? Carbon::parse($weekStart)->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);

        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
        $currentStart = now()->startOfWeek(Carbon::MONDAY);
        $short = ShiftPlanningFormData::weekDayShort();
        $months = [1 => 'Oca', 2 => 'Şub', 3 => 'Mar', 4 => 'Nis', 5 => 'May', 6 => 'Haz', 7 => 'Tem', 8 => 'Ağu', 9 => 'Eyl', 10 => 'Eki', 11 => 'Kas', 12 => 'Ara'];

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $iso = (int) $date->dayOfWeekIso;
            $days[] = [
                'iso' => $iso,
                'date' => $date->toDateString(),
                'day_number' => $date->format('d'),
                'month_short' => $months[(int) $date->format('n')],
                'label_short' => $short[$iso] ?? $date->format('D'),
                'is_today' => $date->isToday(),
            ];
        }

        $label = sprintf(
            '%s %s – %s %s %s',
            $start->format('d'),
            $months[(int) $start->format('n')],
            $end->format('d'),
            $months[(int) $end->format('n')],
            $end->format('Y'),
        );

        return [
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'label' => $label,
            'days' => $days,
            'prev_week' => $start->copy()->subWeek()->toDateString(),
            'next_week' => $start->copy()->addWeek()->toDateString(),
            'is_current' => $start->equalTo($currentStart),
        ];
    }

    /**
     * @param  array<int, mixed>  $courierIds
     * @return array<int, int>
     */
    private function normalizeCourierIds(array $courierIds): array
    {
        return collect($courierIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     start_date: mixed,
     *     end_date: mixed,
     *     start_time: mixed,
     *     end_time: mixed,
     *     days_of_week: mixed,
     *     excluded_dates: mixed,
     * }
     */
    private function schedulePayload(BusinessShift $shift): array
    {
        return [
            'start_date' => $shift->start_date,
            'end_date' => $shift->end_date,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'days_of_week' => $shift->days_of_week,
            'excluded_dates' => $shift->excluded_dates,
        ];
    }
}
