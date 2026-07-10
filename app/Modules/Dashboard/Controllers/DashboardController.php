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
        $user = auth()->user();
        $canFinance = $user?->can('dashboard.financial') ?? false;
        $canEarnings = $user?->can('earning.view') || $user?->can('earning.approve');

        return view('modules.dashboard.index', [
            'stats' => $this->dashboardService->getStats(),
            'latestBusinesses' => $this->dashboardService->getLatestBusinesses(),
            'latestCouriers' => $this->dashboardService->getLatestCouriers(),
            'courierTypeDistribution' => $this->dashboardService->getCourierTypeDistribution(),
            'canFinance' => $canFinance,
            'canEarnings' => $canEarnings,
            'finance' => $canFinance ? $this->dashboardService->getFinanceOverview() : null,
            'pendingCollections' => $canFinance ? $this->dashboardService->getPendingCollections() : [],
            'pendingPayments' => $canFinance ? $this->dashboardService->getPendingPayments() : [],
            'pendingEarnings' => $canEarnings ? $this->dashboardService->getPendingEarnings() : [],
        ]);
    }
}
