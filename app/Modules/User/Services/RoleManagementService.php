<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\User\Data\RoleManagementFormData;
use App\Modules\User\Models\RoleProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementService
{
    public function __construct(
        private readonly RoleManagementPresenter $presenter,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function index(array $filters, int $page = 1, int $perPage = 25): array
    {
        $roles = $this->filter($filters);
        $total = $roles->count();
        $items = $roles
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn (Role $role) => $this->presenter->indexRow($role, $this->userCount($role)))
            ->values()
            ->all();

        return [
            'roles' => $items,
            'summary' => $this->summarize($roles),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function find(int $id): ?array
    {
        $role = Role::query()
            ->with('permissions')
            ->find($id);

        if ($role === null) {
            return null;
        }

        return $this->presenter->detail($role, $this->userCount($role));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Role
    {
        return DB::transaction(function () use ($data, $actor): Role {
            $slug = $this->uniqueRoleSlug($data['display_name']);

            $role = Role::query()->create([
                'name' => $slug,
                'guard_name' => 'web',
            ]);

            RoleProfile::query()->create([
                'role_name' => $slug,
                'display_name' => $data['display_name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'is_system' => false,
            ]);

            $this->activityLog->log(
                'role_created',
                $role,
                description: "{$data['display_name']} rolü oluşturuldu.",
            );

            return $role->fresh('permissions');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $actor): Role
    {
        return DB::transaction(function () use ($id, $data, $actor): Role {
            $role = Role::query()->find($id);

            if ($role === null) {
                abort(404);
            }

            if (! $this->canUpdate($role)) {
                throw ValidationException::withMessages([
                    'role' => 'Bu rol güncellenemez.',
                ]);
            }

            $meta = RoleManagementFormData::meta($role->name);
            $profile = RoleProfile::query()->firstOrCreate(
                ['role_name' => $role->name],
                [
                    'display_name' => $meta['display_name'],
                    'description' => $meta['description'],
                    'status' => $meta['status'],
                    'is_system' => (bool) $meta['is_system'],
                ],
            );

            $profile->update([
                'display_name' => $meta['is_system'] ? $profile->display_name : $data['display_name'],
                'description' => $data['description'] ?? $profile->description,
                'status' => $data['status'] ?? $profile->status,
            ]);

            $this->activityLog->log(
                'role_updated',
                $role,
                description: "{$profile->display_name} rolü güncellendi.",
            );

            return $role->fresh('permissions');
        });
    }

    public function delete(int $id, User $actor): void
    {
        DB::transaction(function () use ($id, $actor): void {
            $role = Role::query()->find($id);

            if ($role === null) {
                abort(404);
            }

            if (! $this->canDelete($role)) {
                throw ValidationException::withMessages([
                    'role' => 'Bu rol silinemez.',
                ]);
            }

            $meta = RoleManagementFormData::meta($role->name);

            RoleProfile::query()->where('role_name', $role->name)->delete();
            $role->delete();

            $this->activityLog->log(
                'role_deleted',
                $role,
                description: "{$meta['display_name']} rolü silindi.",
            );
        });
    }

    public function canUpdate(Role $role): bool
    {
        return true;
    }

    public function canDelete(Role $role): bool
    {
        $meta = RoleManagementFormData::meta($role->name);

        if (! $meta['is_deletable']) {
            return false;
        }

        return $this->userCount($role) === 0;
    }

    private function uniqueRoleSlug(string $displayName): string
    {
        $base = Str::slug($displayName, '_');
        $slug = $base;
        $counter = 2;

        while (Role::query()->where('name', $slug)->exists()) {
            $slug = $base.'_'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function findByName(string $name): ?array
    {
        $role = Role::query()
            ->with('permissions')
            ->where('name', $name)
            ->first();

        if ($role === null) {
            return null;
        }

        return $this->presenter->detail($role, $this->userCount($role));
    }

    /**
     * @param  array<string, string>  $filters
     * @return Collection<int, Role>
     */
    private function filter(array $filters): Collection
    {
        return Role::query()
            ->with('permissions')
            ->orderBy('id')
            ->get()
            ->filter(function (Role $role) use ($filters): bool {
                $meta = RoleManagementFormData::meta($role->name);

                if (! empty($filters['search'])) {
                    $needle = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $meta['display_name'],
                        $role->name,
                        $meta['description'],
                    ]));

                    if (! str_contains($haystack, $needle)) {
                        return false;
                    }
                }

                if (($filters['status'] ?? 'all') !== 'all' && $meta['status'] !== $filters['status']) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    /**
     * @param  Collection<int, Role>  $roles
     * @return array<string, int>
     */
    private function summarize(Collection $roles): array
    {
        return [
            'total_roles' => $roles->count(),
            'active_roles' => $roles
                ->filter(fn (Role $role) => RoleManagementFormData::meta($role->name)['status'] === 'active')
                ->count(),
            'total_users' => User::query()->count(),
            'total_permissions' => Permission::query()->count(),
        ];
    }

    private function userCount(Role $role): int
    {
        return User::query()->role($role->name)->count();
    }
}
