<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\FinanceActivityLogFormData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use App\Modules\Finance\Services\FinanceActivityLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceActivityLogController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly FinanceActivityLogService $activityLogService,
    ) {}

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

        $analysis = $this->activityLogService->analyze($filters, $page, $perPage);

        return view('modules.finance.activity-log.index', [
            'logs' => $analysis['logs'],
            'summary' => $analysis['summary'],
            'logsForModal' => $analysis['logs_for_modal'],
            'filters' => $filters,
            'modules' => FinanceActivityLogFormData::modules(),
            'actionTypes' => FinanceActivityLogFormData::actionTypes(),
            'users' => $this->activityLogService->users(),
            'dateRanges' => FinanceActivityLogFormData::dateRanges(),
            'currentAccounts' => $this->activityLogService->currentAccounts(),
            'total' => $analysis['total'],
            'page' => $analysis['page'],
            'perPage' => $analysis['perPage'],
            'lastPage' => $analysis['lastPage'],
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
