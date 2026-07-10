<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Data\RoleManagementFormData;
use Spatie\Permission\Models\Role;

class RoleManagementPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(Role $role, int $userCount): array
    {
        return $this->enrich($role, $userCount);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Role $role, int $userCount): array
    {
        $permissions = $role->permissions->pluck('name')->sort()->values()->all();

        return array_merge($this->enrich($role, $userCount), [
            'permissions' => $permissions,
            'assigned_users' => $this->assignedUsers($role),
            'permission_groups' => $this->groupPermissions($permissions),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(Role $role, int $userCount): array
    {
        $role->loadMissing('permissions');
        $meta = RoleManagementFormData::meta($role->name);
        $permissions = $role->permissions->pluck('name')->all();
        $status = $meta['status'];

        return [
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $meta['display_name'],
            'description' => $meta['description'],
            'guard_name' => $role->guard_name,
            'status' => $status,
            'status_label' => RoleManagementFormData::statuses()[$status] ?? $status,
            'is_system' => (bool) $meta['is_system'],
            'is_deletable' => (bool) $meta['is_deletable'],
            'can_deactivate' => (bool) $meta['can_deactivate'],
            'icon' => $meta['icon'],
            'color' => $meta['color'],
            'user_count' => $userCount,
            'permission_count' => count($permissions),
            'permissions' => $permissions,
            'created_at' => $role->created_at?->toDateTimeString(),
            'updated_at' => $role->updated_at?->toDateTimeString(),
            'created_at_formatted' => $role->created_at?->format('d.m.Y') ?? '—',
            'updated_at_formatted' => $role->updated_at?->format('d.m.Y H:i') ?? '—',
            'can_update' => auth()->user()?->can('user.update') ?? false,
            'can_delete' => (auth()->user()?->can('user.delete') ?? false) && $meta['is_deletable'] && $userCount === 0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assignedUsers(Role $role): array
    {
        return User::query()
            ->role($role->name)
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'full_name' => trim($user->name),
                'email' => $user->email,
                'status' => $user->trashed() ? 'inactive' : $user->status->value,
                'avatar_initials' => $user->initials(),
                'avatar_color' => $this->avatarColor($user->id),
            ])
            ->all();
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<string, array<int, string>>
     */
    private function groupPermissions(array $permissions): array
    {
        $groups = [];

        foreach ($permissions as $permission) {
            $module = explode('.', $permission)[0];
            $groups[$module][] = $permission;
        }

        ksort($groups);

        return $groups;
    }

    private function avatarColor(int $id): string
    {
        $colors = [
            'bg-blue-600', 'bg-violet-600', 'bg-emerald-600', 'bg-amber-600',
            'bg-rose-600', 'bg-cyan-600', 'bg-indigo-600', 'bg-teal-600',
        ];

        return $colors[$id % count($colors)];
    }
}
