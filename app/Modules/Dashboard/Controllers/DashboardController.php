<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(): View
    {
        return view('modules.dashboard.index', [
            'stats' => $this->dashboardService->getStats(),
            'openingStageBusinesses' => $this->dashboardService->getOpeningStageBusinesses(),
            'latestBusinesses' => $this->dashboardService->getLatestBusinesses(),
            'latestCouriers' => $this->dashboardService->getLatestCouriers(),
            'courierTypeDistribution' => $this->dashboardService->getCourierTypeDistribution(),
        ]);
    }
}
