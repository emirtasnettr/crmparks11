<?php

namespace App\Modules\ShiftPlanning\Services;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Data\ShiftPlanningFormData;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftCourier;
use App\Modules\ShiftPlanning\Models\BusinessShiftJokerAssignment;
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
            ->with(['business', 'rosterCouriers', 'jokerAssignments.absentCourier', 'jokerAssignments.jokerCourier'])
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

        $onRosterIds = BusinessShiftCourier::query()
            ->whereHas('shift', fn ($query) => $query->where('business_id', $businessId))
            ->pluck('courier_id');

        $courierIds = $assignedIds
            ->merge($onRosterIds)
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
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'end_date' => $data['end_date'] ?? now()->addMonth()->toDateString(),
                'required_headcount' => max(1, (int) ($data['required_headcount'] ?? 1)),
                'notes' => $data['notes'] ?? null,
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : true,
                'created_by' => $user->id,
            ]);

            $courierIds = $this->normalizeCourierIds($data['courier_ids'] ?? []);
            if ($courierIds !== []) {
                $this->assertCouriersBelongToBusiness((int) $shift->business_id, $courierIds);
                $shift->rosterCouriers()->sync($courierIds);
            }

            return $shift->fresh(['rosterCouriers']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BusinessShift $shift, array $data): BusinessShift
    {
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

        return $shift->fresh(['rosterCouriers']);
    }

    /**
     * @param  array<int, mixed>  $courierIds
     */
    public function syncRoster(BusinessShift $shift, array $courierIds): BusinessShift
    {
        $normalized = $this->normalizeCourierIds($courierIds);
        $existing = $shift->rosterCouriers()->pluck('couriers.id')->all();
        $this->assertCouriersBelongToBusiness($shift->business_id, $normalized, $existing);

        if (count($normalized) > max(1, (int) $shift->required_headcount)) {
            throw ValidationException::withMessages([
                'courier_ids' => "Bu vardiyada en fazla {$shift->required_headcount} kişi çalışabilir.",
            ]);
        }

        DB::transaction(function () use ($shift, $normalized): void {
            $shift->rosterCouriers()->sync($normalized);

            // Kadrodan çıkan kuryelerin gelecekteki joker kayıtlarını temizle.
            BusinessShiftJokerAssignment::query()
                ->where('business_shift_id', $shift->id)
                ->whereDate('work_date', '>=', now()->toDateString())
                ->whereNotIn('absent_courier_id', $normalized)
                ->delete();
        });

        return $shift->fresh(['rosterCouriers']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assignJoker(BusinessShift $shift, array $data, User $user): BusinessShiftJokerAssignment
    {
        $workDate = Carbon::parse($data['work_date'])->toDateString();
        $absentId = (int) $data['absent_courier_id'];
        $jokerId = (int) $data['joker_courier_id'];

        if ($absentId === $jokerId) {
            throw ValidationException::withMessages([
                'joker_courier_id' => 'Joker personel, izinli kurye ile aynı olamaz.',
            ]);
        }

        $rosterIds = $shift->rosterCouriers()->pluck('couriers.id')->map(fn ($id) => (int) $id)->all();
        if (! in_array($absentId, $rosterIds, true)) {
            throw ValidationException::withMessages([
                'absent_courier_id' => 'İzinli kurye bu vardiyanın kadrosunda olmalıdır.',
            ]);
        }

        if (in_array($jokerId, $rosterIds, true)) {
            throw ValidationException::withMessages([
                'joker_courier_id' => 'Joker personel zaten bu vardiyanın kadrosunda olmamalıdır.',
            ]);
        }

        $this->assertCouriersBelongToBusiness($shift->business_id, [$jokerId]);

        return BusinessShiftJokerAssignment::query()->updateOrCreate(
            [
                'business_shift_id' => $shift->id,
                'work_date' => $workDate,
                'absent_courier_id' => $absentId,
            ],
            [
                'joker_courier_id' => $jokerId,
                'reason' => $data['reason'] ?? 'izin',
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ],
        );
    }

    public function deleteJoker(BusinessShiftJokerAssignment $assignment): void
    {
        $assignment->delete();
    }

    public function findJoker(int $id): ?BusinessShiftJokerAssignment
    {
        return BusinessShiftJokerAssignment::query()
            ->with(['shift', 'absentCourier', 'jokerCourier'])
            ->find($id);
    }

    /**
     * @return Collection<int, BusinessShiftJokerAssignment>
     */
    public function jokersForBusiness(int $businessId, ?string $from = null, ?string $to = null): Collection
    {
        return BusinessShiftJokerAssignment::query()
            ->whereHas('shift', fn ($q) => $q->where('business_id', $businessId))
            ->with(['shift', 'absentCourier', 'jokerCourier'])
            ->when($from, fn ($q) => $q->whereDate('work_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('work_date', '<=', $to))
            ->orderBy('work_date')
            ->orderBy('id')
            ->get();
    }

    public function delete(BusinessShift $shift): void
    {
        DB::transaction(function () use ($shift): void {
            $shift->jokerAssignments()->delete();
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
