<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Data\PermissionManagementDummyData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionManagementController extends Controller
{
    public function index(Request $request): View
    {
        $roles = PermissionManagementDummyData::selectableRoles();
        $selectedRole = $request->string('role')->toString();

        if (! array_key_exists($selectedRole, $roles)) {
            $selectedRole = 'general_manager';
        }

        return view('modules.user.permissions.index', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'summary' => PermissionManagementDummyData::summarize($selectedRole),
            'rolesPayload' => PermissionManagementDummyData::rolesPayload(),
            'actionLabels' => PermissionManagementDummyData::actionLabels(),
        ]);
    }
}
