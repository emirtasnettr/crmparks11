<?php

namespace App\Modules\ShiftPlanning\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Services\ShiftAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftAttendanceController extends Controller
{
    public function __construct(
        private readonly ShiftAttendanceService $attendances,
    ) {}

    public function board(Request $request): View
    {
        $board = $this->attendances->liveOperations(Carbon::today());

        return view('modules.shift-planning.attendance-board', [
            'board' => $board,
            'canManage' => $request->user()?->can('shift_planning.update') ?? false,
        ]);
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

        return redirect()
            ->route('shift-planning.attendance')
            ->with('success', 'Vardiya personel tarafından başlatıldı.');
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

        return redirect()
            ->route('shift-planning.attendance')
            ->with('success', 'Kurye geldi olarak işaretlendi.');
    }

    public function end(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('shift_planning.update'), 403);

        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'attendance_id' => ['required', 'integer', 'exists:business_shift_attendances,id'],
            'work_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'package_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $attendance = BusinessShiftAttendance::query()->findOrFail((int) $data['attendance_id']);
        abort_unless((int) $attendance->business_id === (int) $data['business_id'], 404);

        $this->attendances->endForCourier(
            (int) $data['attendance_id'],
            $request->user(),
            $data['notes'] ?? null,
            array_key_exists('package_count', $data) ? (int) $data['package_count'] : null,
        );

        return redirect()
            ->route('shift-planning.attendance')
            ->with('success', 'Vardiya personel tarafından sonlandırıldı.');
    }
}
