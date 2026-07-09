<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\DashboardFormData;
use App\Modules\Finance\Services\FinanceDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function __construct(
        private readonly FinanceDashboardService $dashboardService,
    ) {}

    public function index(Request $request): View
    {
        $period = $request->string('period')->toString() ?: 'month';
        $startDate = $request->string('start_date')->toString() ?: null;
        $endDate = $request->string('end_date')->toString() ?: null;

        if (! array_key_exists($period, DashboardFormData::periods())) {
            $period = 'month';
        }

        $data = $this->dashboardService->dashboard($period, $startDate, $endDate);
        $data['filters'] = [
            'period' => $period,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
        ];

        return view('modules.finance.dashboard.index', $data);
    }
}
