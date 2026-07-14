<?php

namespace App\Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notification\Data\NotificationFormData;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->string('status')->toString() ?: 'all',
            'type' => $request->string('type')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $result = $this->notificationService->index($request->user(), $filters, $page, $perPage);

        return view('modules.notification.index', [
            'notifications' => $result['notifications'],
            'summary' => $result['summary'],
            'filters' => $filters,
            'types' => NotificationFormData::types(),
            'statuses' => NotificationFormData::statuses(),
            'dateRanges' => NotificationFormData::dateRanges(),
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'lastPage' => $result['lastPage'],
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        abort_unless($request->user()?->can('notification.update'), 403);

        $this->notificationService->markAsRead($request->user(), $id);

        return back()->with('success', 'Bildirim okundu olarak işaretlendi.');
    }

    public function open(Request $request, string $id): RedirectResponse
    {
        $destination = $this->notificationService->open($request->user(), $id);

        return redirect()->to($destination);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('notification.update'), 403);

        $count = $this->notificationService->markAllAsRead($request->user());

        return back()->with('success', "{$count} bildirim okundu olarak işaretlendi.");
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        abort_unless($request->user()?->can('notification.delete'), 403);

        $this->notificationService->delete($request->user(), $id);

        return back()->with('success', 'Bildirim silindi.');
    }
}
