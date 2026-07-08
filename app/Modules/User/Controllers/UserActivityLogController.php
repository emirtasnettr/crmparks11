<?php

namespace App\Modules\User\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\User\Data\UserActivityLogDummyData;
use App\Modules\User\Exports\UserListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserActivityLogController extends Controller
{
    use DownloadsListExport;

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

        $analysis = UserActivityLogDummyData::analyze($filters);
        $all = $analysis['logs'];
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.user.activity-log.index', [
            'logs' => $items,
            'summary' => $analysis['summary'],
            'logsForModal' => $analysis['logs_for_modal'],
            'filters' => $filters,
            'modules' => UserActivityLogDummyData::modules(),
            'activityTypes' => UserActivityLogDummyData::activityTypes(),
            'roles' => UserActivityLogDummyData::roles(),
            'users' => UserActivityLogDummyData::users(),
            'dateRanges' => UserActivityLogDummyData::dateRanges(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
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
}
