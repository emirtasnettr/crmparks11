<?php

namespace App\Modules\ShiftPlanning\Services;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Data\ShiftPlanningFormData;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftDayCourier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftPlanningService
{
    public function __construct(
        private readonly ShiftPlanningPresenter $presenter,
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
    public function forBusiness(int $businessId, ?string $weekStart = null, ?string $weekEnd = null): Collection
    {
        return BusinessShift::query()
            ->where('business_id', $businessId)
            ->when($weekStart && $weekEnd, function ($query) use ($weekStart, $weekEnd): void {
                $query->whereDate('start_date', '<=', $weekEnd)
                    ->whereDate('end_date', '>=', $weekStart);
            })
            ->with(['dayCouriers.courier'])
            ->orderBy('start_time')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?BusinessShift
    {
        return BusinessShift::query()
            ->with(['business', 'dayCouriers.courier'])
            ->find($id);
    }

    public function activeAssignmentCourierCount(int $businessId): int
    {
        return BusinessCourierAssignment::query()
            ->where('business_id', $businessId)
            ->currentlyActive()
            ->pluck('courier_id')
            ->unique()
            ->count();
    }

    /**
     * @return array<int, array{id: int, name: string, phone: string}>
     */
    public function availableCouriers(int $businessId): array
    {
        $assignedIds = BusinessCourierAssignment::query()
            ->where('business_id', $businessId)
            ->currentlyActive()
            ->pluck('courier_id');

        $onShiftIds = BusinessShiftDayCourier::query()
            ->whereHas('shift', fn ($query) => $query->where('business_id', $businessId))
            ->pluck('courier_id');

        $courierIds = $assignedIds
            ->merge($onShiftIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($courierIds === []) {
            return [];
        }

        return Courier::query()
            ->whereIn('id', $courierIds)
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
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'days_of_week' => $this->normalizeDays($data['days_of_week'] ?? null),
                'notes' => $data['notes'] ?? null,
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : true,
                'created_by' => $user->id,
            ]);

            $courierIds = $this->normalizeCourierIds($data['courier_ids'] ?? []);
            if ($courierIds !== []) {
                $this->assertCouriersBelongToBusiness((int) $shift->business_id, $courierIds);
                $this->assignCouriersToAllOccurrences($shift, $courierIds);
            }

            return $shift->fresh(['dayCouriers.courier']);
        });
    }

    /**
     * @param  array<int, int>  $courierIds
     */
    private function assignCouriersToAllOccurrences(BusinessShift $shift, array $courierIds): void
    {
        $rows = [];
        $now = now();

        foreach ($shift->occurrenceDates() as $date) {
            foreach ($courierIds as $courierId) {
                $rows[] = [
                    'business_shift_id' => $shift->id,
                    'work_date' => $date,
                    'courier_id' => $courierId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            BusinessShiftDayCourier::query()->insert($rows);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BusinessShift $shift, array $data): BusinessShift
    {
        return DB::transaction(function () use ($shift, $data): BusinessShift {
            $shift->update([
                'name' => $data['name'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'days_of_week' => $this->normalizeDays($data['days_of_week'] ?? null),
                'notes' => $data['notes'] ?? null,
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : $shift->is_active,
            ]);

            $validDates = $shift->fresh()->occurrenceDates();

            BusinessShiftDayCourier::query()
                ->where('business_shift_id', $shift->id)
                ->whereNotIn('work_date', $validDates)
                ->delete();

            return $shift->fresh(['dayCouriers.courier']);
        });
    }

    /**
     * @param  array<int, mixed>  $courierIds
     */
    public function syncDayCouriers(BusinessShift $shift, string $workDate, array $courierIds): BusinessShift
    {
        $date = Carbon::parse($workDate)->toDateString();

        if (! $shift->runsOnDate($date)) {
            throw ValidationException::withMessages([
                'work_date' => 'Seçilen tarih bu vardiyanın tarih aralığında değil.',
            ]);
        }

        $normalized = $this->normalizeCourierIds($courierIds);
        $existing = BusinessShiftDayCourier::query()
            ->where('business_shift_id', $shift->id)
            ->whereDate('work_date', $date)
            ->pluck('courier_id')
            ->all();

        $this->assertCouriersBelongToBusiness($shift->business_id, $normalized, $existing);

        DB::transaction(function () use ($shift, $date, $normalized): void {
            BusinessShiftDayCourier::query()
                ->where('business_shift_id', $shift->id)
                ->whereDate('work_date', $date)
                ->delete();

            foreach ($normalized as $courierId) {
                BusinessShiftDayCourier::query()->create([
                    'business_shift_id' => $shift->id,
                    'work_date' => $date,
                    'courier_id' => $courierId,
                ]);
            }
        });

        return $shift->fresh(['dayCouriers.courier']);
    }

    public function delete(BusinessShift $shift): void
    {
        DB::transaction(function () use ($shift): void {
            $shift->dayCouriers()->delete();
            $shift->delete();
        });
    }

    public function deleteDay(BusinessShift $shift, string $workDate): string
    {
        $date = Carbon::parse($workDate)->toDateString();

        if (! $this->dateWithinShiftWindow($shift, $date)) {
            throw ValidationException::withMessages([
                'work_date' => 'Seçilen tarih bu vardiyaya ait değil.',
            ]);
        }

        return DB::transaction(function () use ($shift, $date): string {
            $excluded = $shift->excludedDateList();
            if (! in_array($date, $excluded, true)) {
                $excluded[] = $date;
            }

            $shift->update(['excluded_dates' => array_values($excluded)]);

            BusinessShiftDayCourier::query()
                ->where('business_shift_id', $shift->id)
                ->whereDate('work_date', $date)
                ->delete();

            if ($shift->fresh()->occurrenceDates() === []) {
                $shift->dayCouriers()->delete();
                $shift->delete();

                return 'all';
            }

            return 'day';
        });
    }

    private function dateWithinShiftWindow(BusinessShift $shift, string $date): bool
    {
        $day = Carbon::parse($date)->startOfDay();

        if ($shift->start_date && $day->lt($shift->start_date->copy()->startOfDay())) {
            return false;
        }

        if ($shift->end_date && $day->gt($shift->end_date->copy()->startOfDay())) {
            return false;
        }

        return in_array((int) $day->dayOfWeekIso, $shift->activeWeekDays(), true);
    }

    /**
     * @param  array<int, mixed>|null  $days
     * @return array<int, int>
     */
    private function normalizeDays(?array $days): array
    {
        $normalized = collect($days ?? [])
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $normalized !== [] ? $normalized : ShiftPlanningFormData::defaultDays();
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
     * @param  array<int, int>  $courierIds
     * @param  array<int, int>  $alreadyOnShift
     */
    private function assertCouriersBelongToBusiness(int $businessId, array $courierIds, array $alreadyOnShift = []): void
    {
        if ($courierIds === []) {
            return;
        }

        $allowed = BusinessCourierAssignment::query()
            ->where('business_id', $businessId)
            ->currentlyActive()
            ->pluck('courier_id')
            ->map(fn ($id) => (int) $id)
            ->merge(collect($alreadyOnShift)->map(fn ($id) => (int) $id))
            ->unique()
            ->all();

        $invalid = array_values(array_diff($courierIds, $allowed));

        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'courier_ids' => 'Seçilen kuryeler bu işletmeye atanmış olmalıdır.',
            ]);
        }
    }
}
