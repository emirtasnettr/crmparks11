<?php

namespace App\Modules\User\Services;

use App\Core\Enums\Status;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\User\Data\UserManagementFormData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserManagementService
{
    public function __construct(
        private readonly UserManagementPresenter $presenter,
    ) {}

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function index(array $filters, int $page = 1, int $perPage = 25): array
    {
        $users = $this->filter($filters);
        $total = $users->count();
        $items = $users
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn (User $user) => $this->presenter->indexRow($user))
            ->values()
            ->all();

        return [
            'users' => $items,
            'summary' => $this->summarize($users),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(array $filters): array
    {
        return $this->filter($filters)
            ->map(fn (User $user) => $this->presenter->indexRow($user))
            ->values()
            ->all();
    }

    public function find(int $id): ?array
    {
        $user = User::query()
            ->withTrashed()
            ->with(['roles', 'profileable', 'permissions'])
            ->find($id);

        if ($user === null) {
            return null;
        }

        return $this->presenter->detail($user);
    }

    /**
     * @return array<string, string>
     */
    public function roles(): array
    {
        return UserManagementFormData::roleLabels();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function businesses(): array
    {
        return Business::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->company_name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function couriers(): array
    {
        return Courier::query()
            ->orderBy('full_name')
            ->get(['id', 'full_name'])
            ->map(fn (Courier $courier) => [
                'id' => $courier->id,
                'name' => $courier->full_name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function agencies(): array
    {
        return Agency::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (Agency $agency) => [
                'id' => $agency->id,
                'name' => $agency->company_name,
            ])
            ->all();
    }

    /**
     * @param  array<string, string>  $filters
     * @return Collection<int, User>
     */
    private function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with(['roles', 'profileable'])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $today = Carbon::today();

        return User::query()
            ->withTrashed()
            ->when(! empty($filters['search']), function (Builder $query) use ($filters): void {
                $needle = '%'.mb_strtolower($filters['search']).'%';
                $query->where(function (Builder $inner) use ($needle): void {
                    $inner->whereRaw('LOWER(name) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(phone) LIKE ?', [$needle]);
                });
            })
            ->when(($filters['role'] ?? 'all') !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', $filters['role']));
            })
            ->when(($filters['status'] ?? 'all') !== 'all', function (Builder $query) use ($filters): void {
                if ($filters['status'] === 'inactive') {
                    $query->where(function (Builder $inner): void {
                        $inner->where('status', Status::Inactive->value)
                            ->orWhereNotNull('deleted_at');
                    });

                    return;
                }

                $query->where('status', $filters['status'])->whereNull('deleted_at');
            })
            ->when(($filters['last_login'] ?? 'all') !== 'all', function (Builder $query) use ($filters, $today): void {
                match ($filters['last_login']) {
                    'never' => $query->whereNull('last_login_at'),
                    'today' => $query->whereDate('last_login_at', $today),
                    'week' => $query->where('last_login_at', '>=', $today->copy()->startOfWeek()),
                    'month' => $query->where('last_login_at', '>=', $today->copy()->startOfMonth()),
                    default => null,
                };
            });
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<string, int>
     */
    private function summarize(Collection $users): array
    {
        $today = Carbon::today();

        return [
            'total' => $users->count(),
            'active' => $users->filter(fn (User $user) => $user->status === Status::Active && ! $user->trashed())->count(),
            'inactive' => $users->filter(fn (User $user) => $user->status === Status::Inactive || $user->trashed())->count(),
            'logged_in_today' => $users->filter(fn (User $user) => $user->last_login_at?->isSameDay($today))->count(),
        ];
    }
}
