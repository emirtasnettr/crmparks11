<?php

namespace App\Modules\User\Services;

use App\Modules\User\Data\PermissionManagementFormData;
use Spatie\Permission\Models\Role;

class PermissionManagementService
{
    /**
     * @return array<string, string>
     */
    public function selectableRoles(): array
    {
        return PermissionManagementFormData::selectableRoles();
    }

    /**
     * @return array<string, string>
     */
    public function actionLabels(): array
    {
        return PermissionManagementFormData::actionLabels();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function rolesPayload(): array
    {
        $roleSlugs = array_keys(PermissionManagementFormData::selectableRoles());
        $roles = Role::query()
            ->with('permissions')
            ->whereIn('name', $roleSlugs)
            ->get()
            ->keyBy('name');

        $payload = [];

        foreach ($roleSlugs as $roleSlug) {
            $role = $roles->get($roleSlug);
            $grants = $role
                ? $role->permissions->pluck('name')->sort()->values()->all()
                : [];

            $payload[$roleSlug] = [
                'label' => PermissionManagementFormData::selectableRoles()[$roleSlug],
                'is_locked' => $roleSlug === 'super_admin',
                'defaults' => PermissionManagementFormData::defaultGrantsForRole($roleSlug),
                'matrix' => PermissionManagementFormData::buildMatrix($grants),
            ];
        }

        return $payload;
    }

    /**
     * @return array<string, int>
     */
    public function summarize(string $roleSlug): array
    {
        $matrix = $this->rolesPayload()[$roleSlug]['matrix'] ?? [];

        return PermissionManagementFormData::summarizeMatrix($matrix);
    }

    /**
     * @param  array<int, string>  $before
     * @param  array<int, string>  $after
     * @return array<string, mixed>
     */
    public function auditLogPayload(string $roleSlug, array $before, array $after, int $userId = 1): array
    {
        return PermissionManagementFormData::auditLogPayload($roleSlug, $before, $after, $userId);
    }
}
