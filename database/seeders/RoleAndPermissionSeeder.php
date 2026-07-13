<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Kaldırılan roller → hedef rol (kullanıcı atamaları taşınır).
     *
     * @var array<string, string|null>
     */
    private const ROLE_MIGRATIONS = [
        'operations_manager' => 'operations_specialist',
        'operations_staff' => 'operations_specialist',
        'finance_officer' => 'general_manager',
        'regional_coordinator' => 'operations_specialist',
        'reporting_analyst' => 'general_manager',
    ];

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
            'form_application.view',
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
            'sales_manager' => [
                'dashboard.view',
                'business.view', 'business.create', 'business.update',
                'contract.view', 'contract.create', 'contract.update',
                'report.view', 'report.export',
                'form_application.view',
                'notification.view', 'notification.update',
            ],
            'operations_specialist' => [
                'dashboard.view',
                'business.view',
                'courier.view', 'courier.create', 'courier.update',
                'agency.view', 'agency.create', 'agency.update',
                'assignment.view', 'assignment.create', 'assignment.update',
                'shift_planning.view', 'shift_planning.create', 'shift_planning.update', 'shift_planning.delete',
                'form_application.view',
                'notification.view', 'notification.update',
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
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($perms);
        }

        $this->migrateRemovedRoleAssignments(array_keys($rolePermissions));
        $this->deleteRemovedRoles(array_keys($rolePermissions));
    }

    /**
     * @param  array<int, string>  $keptRoles
     */
    private function migrateRemovedRoleAssignments(array $keptRoles): void
    {
        $guard = config('auth.defaults.guard', 'web');

        foreach (self::ROLE_MIGRATIONS as $fromSlug => $toSlug) {
            if ($toSlug === null || ! in_array($toSlug, $keptRoles, true)) {
                continue;
            }

            $fromRole = Role::query()->where('name', $fromSlug)->where('guard_name', $guard)->first();
            $toRole = Role::query()->where('name', $toSlug)->where('guard_name', $guard)->first();

            if ($fromRole === null || $toRole === null) {
                continue;
            }

            $modelType = config('permission.models.user') ?? \App\Models\User::class;

            $modelIds = DB::table(config('permission.table_names.model_has_roles'))
                ->where('role_id', $fromRole->id)
                ->where('model_type', $modelType)
                ->pluck('model_id');

            foreach ($modelIds as $modelId) {
                $alreadyHas = DB::table(config('permission.table_names.model_has_roles'))
                    ->where('role_id', $toRole->id)
                    ->where('model_type', $modelType)
                    ->where('model_id', $modelId)
                    ->exists();

                if (! $alreadyHas) {
                    DB::table(config('permission.table_names.model_has_roles'))->insert([
                        'role_id' => $toRole->id,
                        'model_type' => $modelType,
                        'model_id' => $modelId,
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<int, string>  $keptRoles
     */
    private function deleteRemovedRoles(array $keptRoles): void
    {
        $guard = config('auth.defaults.guard', 'web');

        $removed = Role::query()
            ->where('guard_name', $guard)
            ->whereNotIn('name', $keptRoles)
            ->get();

        foreach ($removed as $role) {
            DB::table(config('permission.table_names.model_has_roles'))
                ->where('role_id', $role->id)
                ->delete();

            DB::table(config('permission.table_names.role_has_permissions'))
                ->where('role_id', $role->id)
                ->delete();

            if (Schema::hasTable('role_profiles')) {
                DB::table('role_profiles')->where('role_name', $role->name)->delete();
            }

            $role->delete();
        }
    }
}
