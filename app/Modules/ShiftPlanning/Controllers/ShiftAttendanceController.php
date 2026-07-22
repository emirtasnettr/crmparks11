<?php

namespace App\Modules\ShiftPlanning\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Services\ShiftAttendanceService;
use App\Modules\ShiftPlanning\Support\ShiftAttendanceRules;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftAttendanceController extends Controller
{
    public function __construct(
        private readonly ShiftAttendanceService $attendances,
    ) {}

    public function board(): RedirectResponse
    {
        return redirect()->route('radar');
    }

    public function start(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('shift_planning.update'), 403);

        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'shift_id' => ['required', 'integer', 'exists:business_shifts,id'],
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
            'work_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $courier = Courier::query()->findOrFail((int) $data['courier_id']);
        $day = Carbon::parse($data['work_date'])->startOfDay();

        $this->attendances->startForCourier(
            $courier,
            (int) $data['shift_id'],
            $day,
            $request->user(),
            $data['notes'] ?? null,
        );

        return $this->redirectAfterStaffAction($request, 'Vardiya personel tarafından başlatıldı.');
    }

    public function markAttended(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('shift_planning.update'), 403);

        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'shift_id' => ['required', 'integer', 'exists:business_shifts,id'],
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
            'work_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $courier = Courier::query()->findOrFail((int) $data['courier_id']);
        $day = Carbon::parse($data['work_date'])->startOfDay();

        $this->attendances->markAttendedForCourier(
            $courier,
            (int) $data['shift_id'],
            $day,
            $request->user(),
            $data['notes'] ?? null,
        );

        return $this->redirectAfterStaffAction($request, 'Kurye geldi olarak işaretlendi.');
    }

    public function end(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('shift_planning.update'), 403);

        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'attendance_id' => ['required', 'integer', 'exists:business_shift_attendances,id'],
            'work_date' => ['required', 'date'],
            'ended_at' => ['required', 'date'],
            'end_reason' => ['nullable', 'string', Rule::in(ShiftAttendanceRules::endReasonCodes())],
            'replacement_courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'package_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $attendance = BusinessShiftAttendance::query()->findOrFail((int) $data['attendance_id']);
        abort_unless((int) $attendance->business_id === (int) $data['business_id'], 404);

        $result = $this->attendances->endForCourier(
            (int) $data['attendance_id'],
            $request->user(),
            [
                'ended_at' => Carbon::parse($data['ended_at']),
                'end_reason' => $data['end_reason'] ?? null,
                'replacement_courier_id' => $data['replacement_courier_id'] ?? null,
                'package_count' => $data['package_count'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
        );

        $message = $result['replacement'] !== null
            ? 'Vardiya sonlandırıldı ve yerine kurye başlatıldı.'
            : 'Vardiya personel tarafından sonlandırıldı.';

        return $this->redirectAfterStaffAction($request, $message);
    }

    private function redirectAfterStaffAction(Request $request, string $message): RedirectResponse
    {
        $fallback = route('shift-planning.index');
        $businessId = $request->integer('business_id') ?: null;
        $week = $request->string('week')->toString() ?: null;

        if ($request->string('return_to')->toString() === 'planning' && $businessId) {
            return redirect()
                ->route('shift-planning.index', array_filter([
                    'business_id' => $businessId,
                    'week' => $week,
                ]))
                ->with('success', $message);
        }

        return redirect()
            ->back(fallback: $fallback)
            ->with('success', $message);
    }
}
