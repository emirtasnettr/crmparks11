<?php

namespace App\Modules\ShiftPlanning\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ShiftPlanning\Requests\AssignShiftCouriersRequest;
use App\Modules\ShiftPlanning\Requests\DestroyBusinessShiftRequest;
use App\Modules\ShiftPlanning\Requests\StoreBusinessShiftRequest;
use App\Modules\ShiftPlanning\Requests\UpdateBusinessShiftRequest;
use App\Modules\ShiftPlanning\Services\ShiftAttendanceService;
use App\Modules\ShiftPlanning\Services\ShiftPlanningPresenter;
use App\Modules\ShiftPlanning\Services\ShiftPlanningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftPlanningController extends Controller
{
    public function __construct(
        private readonly ShiftPlanningService $shifts,
        private readonly ShiftPlanningPresenter $presenter,
        private readonly ShiftAttendanceService $attendances,
    ) {}

    public function index(Request $request): View
    {
        $businessId = $request->integer('business_id') ?: null;
        $business = $businessId ? $this->shifts->findBusiness($businessId) : null;

        if ($businessId && $business === null) {
            abort(404);
        }

        $week = $this->shifts->weekMeta($request->string('week')->toString() ?: null);

        $shifts = $business
            ? $this->shifts->forBusiness($business->id)
            : collect();

        $shiftRows = $shifts
            ->map(fn ($shift) => $this->presenter->indexRow($shift))
            ->values()
            ->all();

        $shiftsById = $shifts->keyBy('id');

        $attendanceSummaries = $business
            ? $this->attendances->weekOccurrenceSummaries(
                $business->id,
                $shifts->pluck('id')->map(fn ($id) => (int) $id)->all(),
                $week['week_start'],
                $week['week_end'],
            )
            : [];

        $calendarDays = [];
        foreach ($week['days'] as $day) {
            $occurrences = [];

            foreach ($shiftRows as $shiftRow) {
                if (! ($shiftRow['is_active'] ?? false)) {
                    continue;
                }

                $shift = $shiftsById->get($shiftRow['id']);
                if ($shift === null || ! $shift->runsOn($day['date'])) {
                    continue;
                }

                $occurrence = $this->presenter->dayOccurrence($shiftRow, $day['date']);
                $summary = $attendanceSummaries[$shiftRow['id'].'|'.$day['date']] ?? null;
                $occurrence['attendance'] = $summary;
                $occurrences[] = $occurrence;
            }

            usort($occurrences, fn ($a, $b) => strcmp($a['start_time_raw'], $b['start_time_raw']));

            $calendarDays[] = [
                ...$day,
                'shifts' => $occurrences,
            ];
        }

        $availableCouriers = $business
            ? $this->shifts->availableCouriers($business->id)
            : [];

        $activeCourierCount = $business
            ? $this->shifts->activeRosterCourierCount($business->id)
            : 0;

        return view('modules.shift-planning.index', [
            'businesses' => $this->shifts->businesses(),
            'selectedBusinessId' => $business?->id,
            'selectedBusinessName' => $business?->displayName(),
            'shifts' => $shiftRows,
            'week' => $week,
            'calendarDays' => $calendarDays,
            'availableCouriers' => $availableCouriers,
            'activeCourierCount' => $activeCourierCount,
            'canCreate' => $request->user()?->can('shift_planning.create') ?? false,
            'canUpdate' => $request->user()?->can('shift_planning.update') ?? false,
            'canDelete' => $request->user()?->can('shift_planning.delete') ?? false,
        ]);
    }

    public function eligibleCouriers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'exclude_shift_id' => ['nullable', 'integer', 'exists:business_shifts,id'],
        ]);

        $couriers = $this->shifts->eligibleCouriersForSchedule(
            [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
            ],
            isset($validated['exclude_shift_id']) ? (int) $validated['exclude_shift_id'] : null,
        );

        return response()->json(['couriers' => $couriers]);
    }

    public function store(StoreBusinessShiftRequest $request): RedirectResponse
    {
        $shift = $this->shifts->create($request->validated(), $request->user());

        return redirect()
            ->route('shift-planning.index', array_filter([
                'business_id' => $shift->business_id,
                'week' => $request->input('week'),
            ]))
            ->with('success', 'Vardiya oluşturuldu.');
    }

    public function update(UpdateBusinessShiftRequest $request, int $id): RedirectResponse
    {
        $shift = $this->shifts->find($id);
        abort_if($shift === null, 404);

        $this->shifts->update($shift, $request->validated());

        return redirect()
            ->route('shift-planning.index', array_filter([
                'business_id' => $shift->business_id,
                'week' => $request->input('week'),
            ]))
            ->with('success', 'Vardiya güncellendi.');
    }

    public function assignCouriers(AssignShiftCouriersRequest $request, int $id): RedirectResponse
    {
        $shift = $this->shifts->find($id);
        abort_if($shift === null, 404);

        $this->shifts->syncRoster($shift, $request->validated('courier_ids') ?? []);

        return redirect()
            ->route('shift-planning.index', array_filter([
                'business_id' => $shift->business_id,
                'week' => $request->input('week'),
            ]))
            ->with('success', 'Vardiya kadrosu güncellendi.');
    }

    public function destroy(DestroyBusinessShiftRequest $request, int $id): RedirectResponse
    {
        $shift = $this->shifts->find($id);
        abort_if($shift === null, 404);

        $businessId = $shift->business_id;
        $this->shifts->delete($shift);

        return redirect()
            ->route('shift-planning.index', array_filter([
                'business_id' => $businessId,
                'week' => $request->input('week'),
            ]))
            ->with('success', 'Vardiya silindi.');
    }
}
