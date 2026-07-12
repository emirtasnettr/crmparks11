<?php

namespace App\Modules\ShiftPlanning\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ShiftPlanning\Data\ShiftPlanningFormData;
use App\Modules\ShiftPlanning\Requests\AssignShiftCouriersRequest;
use App\Modules\ShiftPlanning\Requests\DestroyBusinessShiftRequest;
use App\Modules\ShiftPlanning\Requests\StoreBusinessShiftRequest;
use App\Modules\ShiftPlanning\Requests\UpdateBusinessShiftRequest;
use App\Modules\ShiftPlanning\Services\ShiftPlanningPresenter;
use App\Modules\ShiftPlanning\Services\ShiftPlanningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftPlanningController extends Controller
{
    public function __construct(
        private readonly ShiftPlanningService $shifts,
        private readonly ShiftPlanningPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $businessId = $request->integer('business_id') ?: null;
        $business = $businessId ? $this->shifts->findBusiness($businessId) : null;

        if ($businessId && $business === null) {
            abort(404);
        }

        $week = $this->shifts->weekMeta($request->string('week')->toString() ?: null);

        $shiftRows = $business
            ? $this->shifts->forBusiness($business->id, $week['week_start'], $week['week_end'])
                ->map(fn ($shift) => $this->presenter->indexRow($shift))
                ->values()
                ->all()
            : [];

        $calendarDays = [];
        foreach ($week['days'] as $day) {
            $occurrences = [];
            foreach ($shiftRows as $shiftRow) {
                $occurrence = $this->presenter->occurrenceForDate($shiftRow, $day['date']);
                if ($occurrence !== null) {
                    $occurrences[] = $occurrence;
                }
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
            ? $this->shifts->activeAssignmentCourierCount($business->id)
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
            'weekDays' => ShiftPlanningFormData::weekDayShort(),
            'canCreate' => $request->user()?->can('shift_planning.create') ?? false,
            'canUpdate' => $request->user()?->can('shift_planning.update') ?? false,
            'canDelete' => $request->user()?->can('shift_planning.delete') ?? false,
        ]);
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

        $validated = $request->validated();
        $this->shifts->syncDayCouriers(
            $shift,
            $validated['work_date'],
            $validated['courier_ids'] ?? [],
        );

        return redirect()
            ->route('shift-planning.index', array_filter([
                'business_id' => $shift->business_id,
                'week' => $request->input('week'),
            ]))
            ->with('success', 'Günün kuryeleri güncellendi.');
    }

    public function destroy(DestroyBusinessShiftRequest $request, int $id): RedirectResponse
    {
        $shift = $this->shifts->find($id);
        abort_if($shift === null, 404);

        $businessId = $shift->business_id;
        $scope = $request->validated('scope');

        if ($scope === 'day') {
            $result = $this->shifts->deleteDay($shift, $request->validated('work_date'));
            $message = $result === 'all'
                ? 'Son gün silindiği için vardiyanın tamamı kaldırıldı.'
                : 'Seçilen günün vardiyası silindi.';
        } else {
            $this->shifts->delete($shift);
            $message = 'Aynı saatteki vardiyanın tamamı silindi.';
        }

        return redirect()
            ->route('shift-planning.index', array_filter([
                'business_id' => $businessId,
                'week' => $request->input('week'),
            ]))
            ->with('success', $message);
    }
}
