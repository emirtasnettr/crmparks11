<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\CashFlowFormData;
use App\Modules\Finance\Services\CashFlowService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceCashFlowController extends Controller
{
    public function __construct(
        private readonly CashFlowService $cashFlowService,
    ) {}

    public function index(Request $request): View
    {
        $period = $request->string('period')->toString() ?: 'month';
        $startDate = $request->string('start_date')->toString() ?: null;
        $endDate = $request->string('end_date')->toString() ?: null;

        if (! array_key_exists($period, CashFlowFormData::periods())) {
            $period = 'month';
        }

        $filters = [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'page' => $request->query('page', 1),
        ];

        $analysis = $this->cashFlowService->analyze($filters);

        return view('modules.finance.cash-flow.index', array_merge($analysis, [
            'filters' => [
                'period' => $period,
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
            ],
            'periods' => CashFlowFormData::periods(),
        ]));
    }
}
