<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\FinanceDashboardDummyData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->string('period')->toString() ?: 'month';
        $startDate = $request->string('start_date')->toString() ?: null;
        $endDate = $request->string('end_date')->toString() ?: null;

        if (! array_key_exists($period, FinanceDashboardDummyData::periods())) {
            $period = 'month';
        }

        $data = FinanceDashboardDummyData::dashboard($period, $startDate, $endDate);
        $data['pending_collections'] = FinanceDashboardDummyData::enrichPendingCollections($data['pending_collections']);
        $data['pending_payments'] = FinanceDashboardDummyData::enrichPendingPayments($data['pending_payments']);
        $data['filters'] = [
            'period' => $period,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
        ];

        return view('modules.finance.dashboard.index', $data);
    }
}
