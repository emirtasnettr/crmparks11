<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('notification.view'), 403);

        $filters = [
            'status' => $request->string('status')->toString() ?: 'all',
            'type' => $request->string('type')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));

        $result = $this->notifications->index($request->user(), $filters, $page, $perPage);

        return ApiResponse::paginated(
            $result['notifications'],
            $result['total'],
            $result['page'],
            $result['perPage'],
            'Bildirimler listelendi',
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('notification.view'), 403);

        $preview = $this->notifications->headerPreview($request->user(), 5);

        return ApiResponse::success([
            'unread_count' => $preview['unread_count'],
            'items' => $preview['items'],
        ]);
    }
}
