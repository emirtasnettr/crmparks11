<?php

namespace App\Modules\User\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\User\Data\UserManagementFormData;
use App\Modules\User\Exports\UserListExportSheets;
use App\Modules\User\Services\UserManagementService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserManagementController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly UserManagementService $userService,
    ) {}

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

        $result = $this->userService->index($filters, $page, $perPage);

        return view('modules.user.users.index', [
            'users' => $result['users'],
            'filters' => $filters,
            'roles' => $this->userService->roles(),
            'statuses' => UserManagementFormData::statuses(),
            'lastLoginFilters' => UserManagementFormData::lastLoginFilters(),
            'businesses' => $this->userService->businesses(),
            'couriers' => $this->userService->couriers(),
            'agencies' => $this->userService->agencies(),
            'summary' => $result['summary'],
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'lastPage' => $result['lastPage'],
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
        $user = $this->userService->find($id);

        abort_if($user === null, 404);

        return view('modules.user.users.show', [
            'user' => $user,
            'roles' => $this->userService->roles(),
        ]);
    }
}
