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
            'shift_planning.view', 'shift_planning.create', 'shift_planning.update', 'shift_planning.delete',
            'contract.view', 'contract.create', 'contract.update', 'contract.delete', 'contract.view_own',
            'earning.view', 'earning.create', 'earning.update', 'earning.delete', 'earning.approve', 'earning.view_own',
            'report.view', 'report.export',
            'user.view', 'user.create', 'user.update', 'user.delete',
            'notification.view', 'notification.update', 'notification.delete',
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
            'general_manager' => array_values(array_filter(
                $permissions,
                fn (string $permission) => ! str_starts_with($permission, 'user.') && ! str_starts_with($permission, 'setting.')
            )),
            'operations_manager' => [
                'dashboard.view',
                'business.view', 'business.create', 'business.update',
                'courier.view', 'courier.create', 'courier.update',
                'agency.view', 'agency.create', 'agency.update',
                'assignment.view', 'assignment.create', 'assignment.update',
                'shift_planning.view', 'shift_planning.create', 'shift_planning.update', 'shift_planning.delete',
                'contract.view', 'contract.create', 'contract.update',
                'earning.view', 'earning.create', 'earning.update',
                'notification.view', 'notification.update',
            ],
            'finance_officer' => [
                'dashboard.view', 'dashboard.financial',
                'earning.view', 'earning.approve', 'report.view', 'report.export',
                'notification.view', 'notification.update', 'notification.delete',
            ],
            'operations_staff' => [
                'dashboard.view',
                'business.view', 'courier.view', 'agency.view', 'assignment.view',
                'shift_planning.view',
            ],
            'business' => [
                'dashboard.view',
                'business.view_own',
                'contract.view_own',
                'earning.view_own',
                'notification.view', 'notification.update',
            ],
            'courier' => [
                'dashboard.view',
                'courier.view_own',
                'earning.view_own',
                'contract.view_own',
                'notification.view', 'notification.update',
            ],
            'agency' => [
                'dashboard.view',
                'agency.view_own',
                'courier.view',
                'earning.view_own',
                'contract.view_own',
                'notification.view', 'notification.update',
            ],
            'regional_coordinator' => [
                'dashboard.view',
                'business.view', 'courier.view', 'agency.view', 'assignment.view', 'shift_planning.view', 'report.view',
            ],
            'reporting_analyst' => [
                'dashboard.view', 'dashboard.financial', 'report.view', 'report.export', 'earning.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($perms);
        }
    }
}
