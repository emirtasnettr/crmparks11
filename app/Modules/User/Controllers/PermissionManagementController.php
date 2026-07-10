<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Requests\UpdatePermissionMatrixRequest;
use App\Modules\User\Services\PermissionManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionManagementController extends Controller
{
    public function __construct(
        private readonly PermissionManagementService $permissionService,
    ) {}

    public function index(Request $request): View
    {
        $roles = $this->permissionService->selectableRoles();
        $selectedRole = $request->string('role')->toString();

        if (! array_key_exists($selectedRole, $roles)) {
            $selectedRole = 'general_manager';
        }

        return view('modules.user.permissions.index', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'summary' => $this->permissionService->summarize($selectedRole),
            'rolesPayload' => $this->permissionService->rolesPayload(),
            'actionLabels' => $this->permissionService->actionLabels(),
            'saveUrl' => route('permissions.update'),
        ]);
    }

    public function update(UpdatePermissionMatrixRequest $request): JsonResponse
    {
        $result = $this->permissionService->syncRolePermissions(
            $request->string('role')->toString(),
            $request->input('permissions', []),
            $request->user(),
        );

        return response()->json([
            'message' => 'Yetki değişiklikleri kaydedildi.',
            'role' => $result['role'],
            'summary' => $this->permissionService->summarize($result['role']),
            'role_payload' => $result['role_payload'],
            'added' => array_values(array_diff($result['after'], $result['before'])),
            'removed' => array_values(array_diff($result['before'], $result['after'])),
        ]);
    }
}
