<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Data\RoleManagementDummyData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = RoleManagementDummyData::filter($filters);
        $total = count($all);
        $roles = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.user.roles.index', [
            'roles' => $roles,
            'filters' => $filters,
            'statuses' => RoleManagementDummyData::statuses(),
            'summary' => RoleManagementDummyData::summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function show(int $id): View
    {
        $role = RoleManagementDummyData::find($id);

        abort_if($role === null, 404);

        return view('modules.user.roles.show', [
            'role' => $role,
        ]);
    }
}
