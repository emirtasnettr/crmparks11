<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Data\RoleManagementFormData;
use App\Modules\User\Requests\StoreRoleRequest;
use App\Modules\User\Requests\UpdateRoleRequest;
use App\Modules\User\Services\RoleManagementService;
use Illuminate\Http\RedirectResponse;
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

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roleService->create($request->validated(), $request->user());

        return redirect()
            ->route('roles.show', $role->id)
            ->with('success', 'Rol başarıyla oluşturuldu.');
    }

    public function update(UpdateRoleRequest $request, int $id): RedirectResponse
    {
        $role = $this->roleService->update($id, $request->validated(), $request->user());

        return redirect()
            ->route('roles.show', $role->id)
            ->with('success', 'Rol başarıyla güncellendi.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('user.delete'), 403);

        $this->roleService->delete($id, $request->user());

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol silindi.');
    }

    public function show(int $id): View
    {
        $role = $this->roleService->find($id);

        abort_if($role === null, 404);

        return view('modules.user.roles.show', [
            'role' => $role,
            'statuses' => RoleManagementFormData::statuses(),
        ]);
    }
}
