<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\User\Data\UserActivityLogFormData;
use App\Modules\User\Data\UserManagementFormData;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UserActivityLogPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(ActivityLog $log): array
    {
        return $this->enrich($log);
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(ActivityLog $log): array
    {
        $row = $this->enrich($log);

        return [
            'activity_type_label' => $row['activity_type_label'],
            'occurred_at' => $row['date_formatted'].' '.$row['time_formatted'],
            'user_name' => $row['user_name'],
            'role_label' => $row['role_label'],
            'module_label' => $row['module_label'],
            'ip_address' => $row['ip_address'],
            'browser' => $row['browser'],
            'operating_system' => $row['operating_system'],
            'old_values_json' => $row['old_values_json'],
            'new_values_json' => $row['new_values_json'],
            'description' => $row['description'],
            'user_profile_route' => $row['user_profile_route'],
            'session_insights' => $this->sessionInsights((int) $log->user_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(ActivityLog $log): array
    {
        $log->loadMissing(['user.roles']);
        $occurredAt = $log->created_at ?? now();
        $module = UserActivityLogFormData::resolveModule($log->action, $log->subject_type);
        $oldValues = $log->old_values ?? [];
        $newValues = $log->new_values ?? [];
        $status = UserActivityLogFormData::resolveStatus($log->action, $newValues);
        [$browser, $operatingSystem] = $this->parseUserAgent($log->user_agent);
        $roleSlug = $log->user?->roles->first()?->name;
        $roleLabel = $roleSlug
            ? (UserManagementFormData::roleLabels()[$roleSlug] ?? $roleSlug)
            : '—';

        return [
            'id' => $log->id,
            'log_name' => $module,
            'event' => $log->action,
            'activity_type' => $log->action,
            'module' => $module,
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'causer_type' => User::class,
            'causer_id' => $log->user_id,
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
            'user_id' => $log->user_id,
            'user_name' => $log->user?->name ?? '—',
            'role_slug' => $roleSlug,
            'role_label' => $roleLabel,
            'ip_address' => $log->ip_address ?? '—',
            'user_agent' => $log->user_agent ?? '—',
            'browser' => $browser,
            'operating_system' => $operatingSystem,
            'status' => $status,
            'description' => $log->description ?? '—',
            'properties' => [
                'old' => $oldValues,
                'attributes' => $newValues,
            ],
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'module_label' => UserActivityLogFormData::modules()[$module] ?? $module,
            'activity_type_label' => UserActivityLogFormData::activityLabel($log->action),
            'status_label' => UserActivityLogFormData::statuses()[$status] ?? $status,
            'date_formatted' => $occurredAt->format('d.m.Y'),
            'time_formatted' => $occurredAt->format('H:i:s'),
            'user_profile_route' => $log->user_id ? route('users.show', $log->user_id) : route('users.index'),
            'old_values_json' => json_encode($oldValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}',
            'new_values_json' => json_encode($newValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionInsights(int $userId): array
    {
        $sessions = ActivityLog::query()
            ->where('user_id', $userId)
            ->whereIn('action', ['login', 'logout'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (ActivityLog $log) => $this->enrich($log))
            ->values();

        return [
            'active_session_count' => 0,
            'last_login_at' => $sessions->firstWhere('activity_type', 'login')['occurred_at'] ?? null,
            'last_logout_at' => $sessions->firstWhere('activity_type', 'logout')['occurred_at'] ?? null,
            'recent_sessions' => $sessions->all(),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseUserAgent(?string $userAgent): array
    {
        if (! $userAgent) {
            return ['—', '—'];
        }

        $browser = 'Bilinmiyor';
        $os = 'Bilinmiyor';

        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        } elseif ($userAgent === 'PHPUnit') {
            $browser = 'PHPUnit';
        }

        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif ($userAgent === 'PHPUnit') {
            $os = 'Test';
        }

        return [$browser, $os];
    }
}
