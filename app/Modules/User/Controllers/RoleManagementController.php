<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Data\RoleManagementFormData;
use App\Modules\User\Services\RoleManagementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleManagementController extends Controller
{
    public function __construct(
        private readonly RoleManagementService $roleService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $result = $this->roleService->index($filters, $page, $perPage);

        return view('modules.user.roles.index', [
            'roles' => $result['roles'],
            'filters' => $filters,
            'statuses' => RoleManagementFormData::statuses(),
            'summary' => $result['summary'],
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'lastPage' => $result['lastPage'],
        ]);
    }

    public function show(int $id): View
    {
        $role = $this->roleService->find($id);

        abort_if($role === null, 404);

        return view('modules.user.roles.show', [
            'role' => $role,
        ]);
    }
}
