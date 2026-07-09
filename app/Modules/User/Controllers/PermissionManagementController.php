<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Services\PermissionManagementService;
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
        ]);
    }
}
