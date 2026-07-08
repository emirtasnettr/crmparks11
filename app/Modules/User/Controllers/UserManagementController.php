<?php

namespace App\Modules\User\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\User\Data\UserManagementDummyData;
use App\Modules\User\Exports\UserListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserManagementController extends Controller
{
    use DownloadsListExport;

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'role' => $request->string('role')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'last_login' => $request->string('last_login')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = UserManagementDummyData::filter($filters);
        $total = count($all);
        $users = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.user.users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => UserManagementDummyData::roles(),
            'statuses' => UserManagementDummyData::statuses(),
            'lastLoginFilters' => UserManagementDummyData::lastLoginFilters(),
            'businesses' => UserManagementDummyData::businesses(),
            'couriers' => UserManagementDummyData::couriers(),
            'agencies' => UserManagementDummyData::agencies(),
            'summary' => UserManagementDummyData::summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'role' => $request->string('role')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'last_login' => $request->string('last_login')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'kullanicilar',
            UserListExportSheets::users($filters),
            'Kullanıcılar',
        );
    }

    public function show(int $id): View
    {
        $user = UserManagementDummyData::find($id);

        abort_if($user === null, 404);

        return view('modules.user.users.show', [
            'user' => $user,
            'roles' => UserManagementDummyData::roles(),
        ]);
    }
}
