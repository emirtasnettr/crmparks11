<?php

namespace App\Modules\User\Services;

use App\Core\Enums\UserType;
use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\User\Data\UserManagementFormData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserManagementPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(User $user): array
    {
        return $this->enrich($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(User $user): array
    {
        $row = $this->enrich($user);

        return array_merge($row, [
            'recent_logins' => $this->recentLogins($user),
            'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values()->all(),
            'sessions' => $this->sessions($user),
            'activity_log' => $this->activityLog($user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(User $user): array
    {
        $user->loadMissing(['roles', 'profileable']);

        [$firstName, $lastName] = $this->splitName($user->name);
        $roles = $user->roles->pluck('name')->all();
        $roleLabels = collect($roles)
            ->map(fn (string $role) => UserManagementFormData::roleLabels()[$role] ?? $role)
            ->values()
            ->all();

        $linked = $this->resolveLinkedProfile($user);
        $status = $user->trashed() ? 'inactive' : $user->status->value;

        return [
            'id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => trim($user->name),
            'email' => $user->email,
            'phone' => $user->phone ?? '—',
            'roles' => $roles,
            'role_labels' => $roleLabels,
            'status' => $status,
            'status_label' => UserManagementFormData::statuses()[$status] ?? $user->status->label(),
            'user_type' => $user->user_type->value,
            'user_type_label' => $user->user_type->label(),
            'avatar_initials' => $user->initials(),
            'avatar_color' => $this->avatarColor($user->id),
            'linked_business_id' => $linked['business_id'],
            'linked_business_name' => $linked['business_name'],
            'linked_courier_id' => $linked['courier_id'],
            'linked_courier_name' => $linked['courier_name'],
            'linked_agency_id' => $linked['agency_id'],
            'linked_agency_name' => $linked['agency_name'],
            'linked_unit' => $linked['linked_unit'],
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
            'last_login_formatted' => $user->last_login_at?->format('d.m.Y H:i') ?? '—',
            'last_login_ip' => $user->last_login_ip,
            'two_factor_enabled' => false,
            'two_factor_method' => null,
            'deleted_at' => $user->deleted_at?->toDateTimeString(),
            'created_at' => $user->created_at?->toDateTimeString(),
            'created_at_formatted' => $user->created_at?->format('d.m.Y') ?? '—',
            'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
            'can_update' => auth()->user()?->can('user.update') ?? false,
            'can_delete' => (auth()->user()?->can('user.delete') ?? false) && auth()->id() !== $user->id,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function resolveLinkedProfile(User $user): array
    {
        $profile = $user->profileable;

        $data = [
            'business_id' => null,
            'business_name' => null,
            'courier_id' => null,
            'courier_name' => null,
            'agency_id' => null,
            'agency_name' => null,
            'linked_unit' => $user->user_type === UserType::Internal ? 'Merkez Operasyon' : '—',
        ];

        if ($profile instanceof Business) {
            $data['business_id'] = $profile->id;
            $data['business_name'] = $profile->company_name;
            $data['linked_unit'] = $profile->company_name;
        }

        if ($profile instanceof Courier) {
            $data['courier_id'] = $profile->id;
            $data['courier_name'] = $profile->full_name;
            $data['linked_unit'] = $profile->full_name;
        }

        if ($profile instanceof Agency) {
            $data['agency_id'] = $profile->id;
            $data['agency_name'] = $profile->company_name;
            $data['linked_unit'] = $profile->company_name;
        }

        return $data;
    }

    private function avatarColor(int $id): string
    {
        $colors = [
            'bg-blue-600', 'bg-violet-600', 'bg-emerald-600', 'bg-amber-600',
            'bg-rose-600', 'bg-cyan-600', 'bg-indigo-600', 'bg-teal-600',
        ];

        return $colors[$id % count($colors)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentLogins(User $user): array
    {
        if ($user->last_login_at === null) {
            return [];
        }

        return [[
            'logged_in_at' => $user->last_login_at->format('d.m.Y H:i'),
            'ip' => $user->last_login_ip ?? '—',
            'device' => 'Son oturum',
            'location' => '—',
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sessions(User $user): array
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->limit(5)
            ->get()
            ->map(function (object $session, int $index) use ($user): array {
                return [
                    'device' => $session->user_agent ? mb_substr((string) $session->user_agent, 0, 80) : 'Bilinmiyor',
                    'ip' => $session->ip_address ?? '—',
                    'last_active' => Carbon::createFromTimestamp((int) $session->last_activity)->format('d.m.Y H:i'),
                    'current' => $index === 0 && $user->last_login_ip === $session->ip_address,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activityLog(User $user): array
    {
        return ActivityLog::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'action' => $log->action,
                'description' => $log->description ?? '—',
                'performed_at' => $log->created_at?->format('d.m.Y H:i') ?? '—',
                'ip' => $log->ip_address ?? '—',
            ])
            ->all();
    }
}
