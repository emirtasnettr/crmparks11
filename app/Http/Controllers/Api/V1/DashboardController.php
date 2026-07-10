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

        $user = $request->user();
        $canFinance = $user->can('dashboard.financial');
        $canEarnings = $user->can('earning.view') || $user->can('earning.approve');

        $data = [
            'stats' => $this->dashboard->getStats(),
            'latest_businesses' => $this->dashboard->getLatestBusinesses(5),
            'latest_couriers' => $this->dashboard->getLatestCouriers(5),
            'courier_type_distribution' => $this->dashboard->getCourierTypeDistribution(),
        ];

        if ($canFinance) {
            $data['finance'] = $this->dashboard->getFinanceOverview();
            $data['pending_collections'] = $this->dashboard->getPendingCollections();
            $data['pending_payments'] = $this->dashboard->getPendingPayments();
        }

        if ($canEarnings) {
            $data['pending_earnings'] = $this->dashboard->getPendingEarnings();
        }

        return ApiResponse::success($data, 'Dashboard verileri');
    }
}
