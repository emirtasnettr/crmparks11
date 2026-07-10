<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\User\Data\PermissionManagementFormData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionManagementService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}
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
     * @param  array<int, string>  $grantedSlugs
     * @return array{role: string, before: array<int, string>, after: array<int, string>, role_payload: array<string, mixed>}
     */
    public function syncRolePermissions(string $roleSlug, array $grantedSlugs, User $user): array
    {
        if ($roleSlug === 'super_admin') {
            throw ValidationException::withMessages([
                'role' => 'Süper Admin yetkileri değiştirilemez.',
            ]);
        }

        return DB::transaction(function () use ($roleSlug, $grantedSlugs, $user): array {
            $role = Role::query()->where('name', $roleSlug)->firstOrFail();
            $matrixSlugs = array_flip(PermissionManagementFormData::allMatrixPermissionSlugs());
            $existingPermissions = Permission::query()->pluck('name')->flip()->all();

            $before = $role->permissions->pluck('name')->sort()->values()->all();

            $nonMatrix = collect($before)
                ->reject(fn (string $permission) => isset($matrixSlugs[$permission]))
                ->values()
                ->all();

            $newMatrixGrants = collect($grantedSlugs)
                ->filter(fn (string $slug) => isset($matrixSlugs[$slug]) && isset($existingPermissions[$slug]))
                ->unique()
                ->sort()
                ->values()
                ->all();

            $after = collect(array_merge($nonMatrix, $newMatrixGrants))
                ->unique()
                ->sort()
                ->values()
                ->all();

            $role->syncPermissions($after);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $roleLabel = PermissionManagementFormData::selectableRoles()[$roleSlug] ?? $roleSlug;
            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));

            $this->activityLog->log(
                'permission_updated',
                $role,
                oldValues: [
                    'role' => $roleSlug,
                    'permissions' => $before,
                ],
                newValues: [
                    'role' => $roleSlug,
                    'permissions' => $after,
                    'added' => $added,
                    'removed' => $removed,
                ],
                description: "{$roleLabel} rolünün yetkileri güncellendi.",
            );

            return [
                'role' => $roleSlug,
                'before' => $before,
                'after' => $after,
                'role_payload' => $this->buildRolePayload($roleSlug, $after),
            ];
        });
    }

    /**
     * @param  array<int, string>  $grants
     * @return array<string, mixed>
     */
    private function buildRolePayload(string $roleSlug, array $grants): array
    {
        return [
            'label' => PermissionManagementFormData::selectableRoles()[$roleSlug],
            'is_locked' => false,
            'defaults' => PermissionManagementFormData::defaultGrantsForRole($roleSlug),
            'matrix' => PermissionManagementFormData::buildMatrix($grants),
        ];
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
