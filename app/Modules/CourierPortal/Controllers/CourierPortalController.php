<?php

namespace App\Modules\CourierPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ShiftPlanning\Services\ShiftAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierPortalController extends Controller
{
    public function __construct(
        private readonly ShiftAttendanceService $attendances,
    ) {}

    public function dashboard(Request $request): View
    {
        $courier = $this->attendances->resolveCourierForUser($request->user());
        $payload = $this->attendances->portalPayload($courier);

        return view('modules.courier-portal.dashboard', [
            'courier' => [
                'id' => $courier->id,
                'full_name' => $courier->full_name,
            ],
            'today' => $payload['today'],
            'recent' => $payload['recent'],
            'summary' => $payload['summary'],
        ]);
    }

    public function startShift(Request $request, int $shiftId): RedirectResponse
    {
        $courier = $this->attendances->resolveCourierForUser($request->user());
        $this->attendances->start($courier, $shiftId);

        return redirect()
            ->route('courier-portal.dashboard')
            ->with('success', 'Vardiya başlatıldı.');
    }

    public function endShift(Request $request, int $attendanceId): RedirectResponse
    {
        $courier = $this->attendances->resolveCourierForUser($request->user());
        $this->attendances->end($courier, $attendanceId);

        return redirect()
            ->route('courier-portal.dashboard')
            ->with('success', 'Vardiya sonlandırıldı. Çalışma süresi kaydedildi.');
    }
}
