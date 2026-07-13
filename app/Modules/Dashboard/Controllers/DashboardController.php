<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        $isSalesDashboard = $request->user()?->hasRole('sales_manager') ?? false;

        $payload = [
            'isSalesDashboard' => $isSalesDashboard,
            'openingStageBusinesses' => $this->dashboardService->getOpeningStageBusinesses(),
        ];

        if ($isSalesDashboard) {
            return view('modules.dashboard.index', array_merge($payload, [
                'salesStats' => $this->dashboardService->getSalesStats(),
                'businessStatusDistribution' => $this->dashboardService->getBusinessStatusDistribution(),
                'latestBusinesses' => $this->dashboardService->getLatestBusinesses(),
                'expiringContracts' => $this->dashboardService->getExpiringContracts(),
                'latestFormSubmissions' => $this->dashboardService->getLatestFormSubmissions(),
            ]));
        }

        return view('modules.dashboard.index', array_merge($payload, [
            'stats' => $this->dashboardService->getStats(),
            'latestBusinesses' => $this->dashboardService->getLatestBusinesses(),
            'latestCouriers' => $this->dashboardService->getLatestCouriers(),
            'courierTypeDistribution' => $this->dashboardService->getCourierTypeDistribution(),
        ]));
    }
}
