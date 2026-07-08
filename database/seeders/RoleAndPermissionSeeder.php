<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'dashboard.financial',
            'business.view', 'business.create', 'business.update', 'business.delete', 'business.view_own',
            'courier.view', 'courier.create', 'courier.update', 'courier.delete', 'courier.view_own',
            'agency.view', 'agency.create', 'agency.update', 'agency.delete', 'agency.view_own',
            'assignment.view', 'assignment.create', 'assignment.update', 'assignment.delete',
            'contract.view', 'contract.create', 'contract.update', 'contract.delete', 'contract.view_own',
            'earning.view', 'earning.create', 'earning.update', 'earning.delete', 'earning.approve', 'earning.view_own',
            'report.view', 'report.export',
            'user.view', 'user.create', 'user.update', 'user.delete',
            'setting.view', 'setting.update',
            'form_builder.view', 'form_builder.manage',
            'landing_page.view', 'landing_page.manage',
            'policy_settings.view', 'policy_settings.manage',
            'activity_log.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $rolePermissions = [
            'super_admin' => $permissions,
            'general_manager' => array_filter($permissions, fn ($p) => ! str_starts_with($p, 'user.') && ! str_starts_with($p, 'setting.')),
            'operations_manager' => [
                'dashboard.view',
                'business.view', 'business.create', 'business.update',
                'courier.view', 'courier.create', 'courier.update',
                'agency.view', 'agency.create', 'agency.update',
                'assignment.view', 'assignment.create', 'assignment.update',
                'contract.view', 'contract.create', 'contract.update',
                'earning.view', 'earning.create', 'earning.update',
            ],
            'courier' => [
                'dashboard.view',
                'courier.view_own',
                'earning.view_own',
                'contract.view_own',
            ],
            'business' => [
                'dashboard.view',
                'business.view_own',
                'contract.view_own',
                'earning.view_own',
            ],
            'agency' => [
                'dashboard.view',
                'agency.view_own',
                'courier.view',
                'earning.view_own',
                'contract.view_own',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($perms);
        }
    }
}
