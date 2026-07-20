<?php

namespace App\Modules\User\Services;

use App\Core\Enums\Status;
use App\Core\Enums\UserType;
use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Notification\Services\UserNotificationService;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\User\Data\UserManagementFormData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function __construct(
        private readonly UserManagementPresenter $presenter,
        private readonly ActivityLogService $activityLog,
        private readonly UserNotificationService $userNotifications,
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
     * @return array<string, string>
     */
    public function assignableRoles(): array
    {
        return UserManagementFormData::assignableRoleLabels();
    }

    /**
     * Kurye kaydı olmayan (manuel eklenmiş) kurye-rolü hesaplarını pasife alır.
     */
    public function deactivateOrphanCourierAccounts(): int
    {
        $orphans = User::role('courier')
            ->where(function (Builder $query): void {
                $query->whereNull('profileable_type')
                    ->orWhere('profileable_type', '!=', Courier::class)
                    ->orWhereNull('profileable_id');
            })
            ->get();

        $count = 0;

        foreach ($orphans as $orphan) {
            if ($orphan->trashed()) {
                continue;
            }

            $orphan->update(['status' => Status::Inactive]);
            $orphan->delete();
            $count++;
        }

        return $count;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function businesses(): array
    {
        return Business::query()
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->displayName(),
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
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Agency $agency) => [
                'id' => $agency->id,
                'name' => $agency->displayName(),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $this->assertNoCourierRoleAssignment($data['roles'] ?? []);

            [$userType, $profileableType, $profileableId] = $this->resolveProfile($data);

            $user = User::query()->create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'user_type' => $userType,
                'profileable_type' => $profileableType,
                'profileable_id' => $profileableId,
                'status' => Status::from($data['status']),
            ]);

            $user->syncRoles($data['roles']);

            $this->activityLog->log(
                'user_created',
                $user,
                description: "{$user->name} kullanıcısı oluşturuldu.",
            );

            $user = $user->fresh(['roles', 'profileable']);
            $this->userNotifications->notifyCreated($user, $actor);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $actor): User
    {
        return DB::transaction(function () use ($id, $data, $actor): User {
            $user = User::query()->withTrashed()->find($id);

            if ($user === null) {
                abort(404);
            }

            if (! $this->canUpdate($user, $actor)) {
                throw ValidationException::withMessages([
                    'user' => $this->isCourierManagedAccount($user)
                        ? 'Kurye hesapları yalnızca Kuryeler modülünden yönetilir.'
                        : 'Bu kullanıcı güncellenemez.',
                ]);
            }

            $this->assertNoCourierRoleAssignment($data['roles'] ?? []);

            [$userType, $profileableType, $profileableId] = $this->resolveProfile($data);
            $status = Status::from($data['status']);

            $oldValues = $user->only(['name', 'email', 'phone', 'status', 'user_type']);

            $updates = [
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'user_type' => $userType,
                'profileable_type' => $profileableType,
                'profileable_id' => $profileableId,
                'status' => $status,
            ];

            if (! empty($data['password'])) {
                $updates['password'] = Hash::make($data['password']);
            }

            if ($user->trashed()) {
                $user->restore();
            }

            $user->update($updates);
            $user->syncRoles($data['roles']);

            if ($status === Status::Inactive) {
                $user->delete();
            }

            $this->activityLog->log(
                'user_updated',
                $user,
                description: "{$user->name} kullanıcısı güncellendi.",
                oldValues: $oldValues,
                newValues: $user->fresh()->only(array_keys($oldValues)),
            );

            return $user->fresh(['roles', 'profileable']);
        });
    }

    public function delete(int $id, User $actor): void
    {
        DB::transaction(function () use ($id, $actor): void {
            $user = User::query()->find($id);

            if ($user === null) {
                abort(404);
            }

            if (! $this->canDelete($user, $actor)) {
                throw ValidationException::withMessages([
                    'user' => 'Bu kullanıcı silinemez.',
                ]);
            }

            $user->update(['status' => Status::Inactive]);
            $user->delete();

            $this->activityLog->log(
                'user_deleted',
                $user,
                description: "{$user->name} kullanıcısı pasife alındı.",
            );
        });
    }

    public function forceDelete(int $id, User $actor): void
    {
        DB::transaction(function () use ($id, $actor): void {
            $user = User::withTrashed()->find($id);

            if ($user === null) {
                abort(404);
            }

            if (! $this->canForceDelete($user, $actor)) {
                throw ValidationException::withMessages([
                    'user' => 'Bu kullanıcı kalıcı olarak silinemez.',
                ]);
            }

            $name = $user->name;

            $this->activityLog->log(
                'user_force_deleted',
                $user,
                description: "{$name} kullanıcısı kalıcı olarak silindi.",
            );

            DB::table('period_locks')->where('locked_by', $user->id)->delete();

            $user->tokens()->delete();
            $user->roles()->detach();
            $user->permissions()->detach();
            $user->forceDelete();
        });
    }

    public function setStatus(int $id, Status $status, User $actor): User
    {
        return DB::transaction(function () use ($id, $status, $actor): User {
            $user = User::query()->find($id);

            if ($user === null) {
                abort(404);
            }

            if (! $this->canUpdate($user, $actor)) {
                throw ValidationException::withMessages([
                    'user' => 'Bu kullanıcının durumu güncellenemez.',
                ]);
            }

            if ($status === Status::Inactive && ! $this->canDelete($user, $actor)) {
                throw ValidationException::withMessages([
                    'user' => 'Bu kullanıcı pasife alınamaz.',
                ]);
            }

            $oldStatus = $user->status;
            $user->update(['status' => $status]);

            $this->activityLog->log(
                'user_updated',
                $user,
                oldValues: ['status' => $oldStatus?->value ?? (string) $oldStatus],
                newValues: ['status' => $status->value],
                description: "{$user->name} durumu {$status->label()} olarak güncellendi.",
            );

            return $user->fresh(['roles', 'profileable']);
        });
    }

    public function canUpdate(User $user, User $actor): bool
    {
        if ($this->isCourierManagedAccount($user)) {
            return false;
        }

        return $actor->can('user.update');
    }

    public function isCourierManagedAccount(User $user): bool
    {
        return $user->hasRole('courier')
            || $user->user_type === UserType::Courier
            || $user->profileable_type === Courier::class;
    }

    /**
     * @param  list<string>  $roles
     */
    private function assertNoCourierRoleAssignment(array $roles): void
    {
        if (in_array('courier', $roles, true)) {
            throw ValidationException::withMessages([
                'roles' => 'Kurye hesapları Kullanıcılar ekranından oluşturulamaz. Kuryeler modülünden kurye ekleyin.',
            ]);
        }
    }

    public function canDelete(User $user, User $actor): bool
    {
        if ($actor->id === $user->id) {
            return false;
        }

        if ($user->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            return false;
        }

        return true;
    }

    public function canForceDelete(User $user, User $actor): bool
    {
        if (! $actor->hasRole('super_admin')) {
            return false;
        }

        return $this->canDelete($user, $actor);
    }

    public function sendPasswordResetLink(int $id, User $actor): void
    {
        $user = User::query()->find($id);

        if ($user === null) {
            abort(404);
        }

        if (! $this->canUpdate($user, $actor)) {
            throw ValidationException::withMessages([
                'user' => 'Bu kullanıcı için şifre sıfırlanamaz.',
            ]);
        }

        if ($user->status !== Status::Active) {
            throw ValidationException::withMessages([
                'user' => 'Pasif kullanıcı için şifre sıfırlama gönderilemez.',
            ]);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'user' => __($status),
            ]);
        }

        $this->activityLog->log(
            'password_reset_sent',
            $user,
            description: "{$user->name} için şifre sıfırlama bağlantısı gönderildi.",
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: UserType, 1: ?string, 2: ?int}
     */
    private function resolveProfile(array $data): array
    {
        $roles = $data['roles'] ?? [];

        if (! empty($data['linked_business_id']) || in_array('business', $roles, true)) {
            $businessId = ! empty($data['linked_business_id']) ? (int) $data['linked_business_id'] : null;

            return [UserType::Business, $businessId ? Business::class : null, $businessId];
        }

        if (! empty($data['linked_agency_id']) || in_array('agency', $roles, true)) {
            $agencyId = ! empty($data['linked_agency_id']) ? (int) $data['linked_agency_id'] : null;

            return [UserType::Agency, $agencyId ? Agency::class : null, $agencyId];
        }

        return [UserType::Internal, null, null];
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
