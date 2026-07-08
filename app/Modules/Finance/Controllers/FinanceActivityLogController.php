<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\FinanceActivityLogDummyData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceActivityLogController extends Controller
{
    use DownloadsListExport;

    public function index(Request $request): View
    {
        $filters = [
            'action_type' => $request->string('action_type')->toString() ?: 'all',
            'module' => $request->string('module')->toString() ?: 'all',
            'user_id' => $request->string('user_id')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'current_account' => $request->string('current_account')->toString() ?: 'all',
            'reference' => $request->string('reference')->toString() ?: '',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $analysis = FinanceActivityLogDummyData::analyze($filters);
        $all = $analysis['logs'];
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.finance.activity-log.index', [
            'logs' => $items,
            'summary' => $analysis['summary'],
            'logsForModal' => $analysis['logs_for_modal'],
            'filters' => $filters,
            'modules' => FinanceActivityLogDummyData::modules(),
            'actionTypes' => FinanceActivityLogDummyData::actionTypes(),
            'users' => FinanceActivityLogDummyData::users(),
            'dateRanges' => FinanceActivityLogDummyData::dateRanges(),
            'currentAccounts' => FinanceActivityLogDummyData::currentAccounts(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'action_type' => $request->string('action_type')->toString() ?: 'all',
            'module' => $request->string('module')->toString() ?: 'all',
            'user_id' => $request->string('user_id')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'current_account' => $request->string('current_account')->toString() ?: 'all',
            'reference' => $request->string('reference')->toString() ?: '',
        ];

        return $this->downloadExportSheet(
            'finans-hareket-gecmisi',
            FinanceListExportSheets::activityLog($filters),
            'Finans Hareket Geçmişi',
        );
    }
}
