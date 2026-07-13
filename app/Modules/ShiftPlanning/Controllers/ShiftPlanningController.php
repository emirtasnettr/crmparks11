<?php

namespace App\Modules\ShiftPlanning\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ShiftPlanning\Data\ShiftPlanningFormData;
use App\Modules\ShiftPlanning\Requests\AssignShiftCouriersRequest;
use App\Modules\ShiftPlanning\Requests\DestroyBusinessShiftRequest;
use App\Modules\ShiftPlanning\Requests\StoreBusinessShiftRequest;
use App\Modules\ShiftPlanning\Requests\StoreShiftJokerRequest;
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
            ? $this->shifts->forBusiness($business->id)
                ->map(fn ($shift) => $this->presenter->indexRow($shift))
                ->values()
                ->all()
            : [];

        $jokerRows = $business
            ? $this->shifts->jokersForBusiness($business->id, $week['week_start'], $week['week_end'])
                ->map(fn ($joker) => $this->presenter->jokerRow($joker))
                ->values()
                ->all()
            : [];

        $upcomingJokers = $business
            ? $this->shifts->jokersForBusiness($business->id, now()->toDateString())
                ->take(20)
                ->map(fn ($joker) => $this->presenter->jokerRow($joker))
                ->values()
                ->all()
            : [];

        $calendarDays = [];
        foreach ($week['days'] as $day) {
            $dayJokers = collect($jokerRows)->where('work_date', $day['date'])->values()->all();
            $occurrences = [];

            foreach ($shiftRows as $shiftRow) {
                if (! ($shiftRow['is_active'] ?? false)) {
                    continue;
                }

                $occurrences[] = $this->presenter->dayOccurrence($shiftRow, $day['date'], $dayJokers);
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
            'jokers' => $upcomingJokers,
            'weekJokers' => $jokerRows,
            'availableCouriers' => $availableCouriers,
            'activeCourierCount' => $activeCourierCount,
            'jokerReasons' => ShiftPlanningFormData::jokerReasons(),
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

        $this->shifts->syncRoster($shift, $request->validated('courier_ids') ?? []);

        return redirect()
            ->route('shift-planning.index', array_filter([
                'business_id' => $shift->business_id,
                'week' => $request->input('week'),
            ]))
            ->with('success', 'Vardiya kadrosu güncellendi.');
    }

    public function storeJoker(StoreShiftJokerRequest $request, int $id): RedirectResponse
    {
        $shift = $this->shifts->find($id);
        abort_if($shift === null, 404);

        $this->shifts->assignJoker($shift, $request->validated(), $request->user());

        return redirect()
            ->route('shift-planning.index', array_filter([
                'business_id' => $shift->business_id,
                'week' => $request->input('week'),
            ]))
            ->with('success', 'Joker personel atandı.');
    }

    public function destroyJoker(Request $request, int $jokerId): RedirectResponse
    {
        abort_unless($request->user()?->can('shift_planning.update'), 403);

        $joker = $this->shifts->findJoker($jokerId);
        abort_if($joker === null, 404);

        $businessId = $joker->shift?->business_id;
        $this->shifts->deleteJoker($joker);

        return redirect()
            ->route('shift-planning.index', array_filter([
                'business_id' => $businessId,
                'week' => $request->input('week'),
            ]))
            ->with('success', 'Joker ataması kaldırıldı.');
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
