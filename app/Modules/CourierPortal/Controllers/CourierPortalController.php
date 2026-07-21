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
            'today' => $payload['today'],
            'upcoming' => $payload['upcoming'],
        ]);
    }

    public function earnings(Request $request): View
    {
        $courier = $this->attendances->resolveCourierForUser($request->user());
        $payload = $this->attendances->portalPayload($courier);

        return view('modules.courier-portal.earnings', [
            'recent' => $payload['recent'],
            'summary' => $payload['summary'],
        ]);
    }

    public function profile(Request $request): View
    {
        $courier = $this->attendances->resolveCourierForUser($request->user());
        $courier->loadMissing('user');

        return view('modules.courier-portal.profile', [
            'courier' => [
                'id' => $courier->id,
                'full_name' => $courier->full_name,
                'phone' => $courier->phone,
                'email' => $courier->email,
                'login_email' => $courier->user?->email,
            ],
        ]);
    }

    public function startShift(Request $request, int $shiftId): RedirectResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ], [
            'latitude.required' => 'Vardiya başlatmak için konum izni gereklidir.',
            'longitude.required' => 'Vardiya başlatmak için konum izni gereklidir.',
        ]);

        $courier = $this->attendances->resolveCourierForUser($request->user());
        $this->attendances->start($courier, $shiftId, options: [
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'] ?? null,
        ]);

        return redirect()
            ->route('courier-portal.dashboard')
            ->with('success', 'Vardiya başlatıldı.');
    }

    public function endShift(Request $request, int $attendanceId): RedirectResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ], [
            'latitude.required' => 'Vardiya sonlandırmak için konum izni gereklidir.',
            'longitude.required' => 'Vardiya sonlandırmak için konum izni gereklidir.',
        ]);

        $courier = $this->attendances->resolveCourierForUser($request->user());
        $this->attendances->end($courier, $attendanceId, [
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'] ?? null,
        ]);

        return redirect()
            ->route('courier-portal.dashboard')
            ->with('success', 'Vardiya sonlandırıldı. Çalışma süresi kaydedildi.');
    }
}
