<?php

namespace App\Modules\User\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Core\Http\Concerns\DownloadsPdfExport;
use App\Http\Controllers\Controller;
use App\Modules\User\Data\UserActivityLogFormData;
use App\Modules\User\Exports\UserListExportSheets;
use App\Modules\User\Services\UserActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserActivityLogController extends Controller
{
    use DownloadsListExport;
    use DownloadsPdfExport;

    public function __construct(
        private readonly UserActivityLogService $activityLogService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'user_id' => $request->string('user_id')->toString() ?: 'all',
            'role' => $request->string('role')->toString() ?: 'all',
            'activity_type' => $request->string('activity_type')->toString() ?: 'all',
            'module' => $request->string('module')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'ip_address' => $request->string('ip_address')->toString(),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $analysis = $this->activityLogService->analyze($filters, $page, $perPage);

        return view('modules.user.activity-log.index', [
            'logs' => $analysis['logs'],
            'summary' => $analysis['summary'],
            'logsForModal' => $analysis['logs_for_modal'],
            'filters' => $filters,
            'modules' => UserActivityLogFormData::modules(),
            'activityTypes' => UserActivityLogFormData::activityTypes(),
            'roles' => UserActivityLogFormData::roles(),
            'users' => $this->activityLogService->users(),
            'dateRanges' => UserActivityLogFormData::dateRanges(),
            'total' => $analysis['total'],
            'page' => $analysis['page'],
            'perPage' => $analysis['perPage'],
            'lastPage' => $analysis['lastPage'],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'user_id' => $request->string('user_id')->toString() ?: 'all',
            'role' => $request->string('role')->toString() ?: 'all',
            'activity_type' => $request->string('activity_type')->toString() ?: 'all',
            'module' => $request->string('module')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'ip_address' => $request->string('ip_address')->toString(),
        ];

        return $this->downloadExportSheet(
            'kullanici-aktivite-kayitlari',
            UserListExportSheets::activityLog($filters),
            'Aktivite Kayıtları',
        );
    }

    public function exportPdf(Request $request): Response
    {
        $filters = [
            'user_id' => $request->string('user_id')->toString() ?: 'all',
            'role' => $request->string('role')->toString() ?: 'all',
            'activity_type' => $request->string('activity_type')->toString() ?: 'all',
            'module' => $request->string('module')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'ip_address' => $request->string('ip_address')->toString(),
        ];

        return $this->downloadPdfTable(
            'Aktivite Kayıtları',
            UserListExportSheets::activityLog($filters),
            'kullanici-aktivite-kayitlari',
        );
    }
}
