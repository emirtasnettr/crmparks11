<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Data\RoleManagementFormData;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementService
{
    public function __construct(
        private readonly RoleManagementPresenter $presenter,
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
