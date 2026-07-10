<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        return ApiResponse::success([
            'stats' => $this->dashboard->getStats(),
            'latest_businesses' => $this->dashboard->getLatestBusinesses(5),
            'latest_couriers' => $this->dashboard->getLatestCouriers(5),
        ], 'Dashboard verileri');
    }
}
