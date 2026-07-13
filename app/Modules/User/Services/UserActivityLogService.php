<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\User\Data\UserActivityLogFormData;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UserActivityLogService
{
    public function __construct(
        private readonly UserActivityLogPresenter $presenter,
    ) {}

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function analyze(array $filters, int $page = 1, int $perPage = 25): array
    {
        $allLogs = $this->allLogs();
        $filteredLogs = $this->applyFilters($allLogs, $filters);
        $rows = $filteredLogs->map(fn (ActivityLog $log) => $this->presenter->indexRow($log))->values();

        $total = $rows->count();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values()->all();
        $allRows = $allLogs->map(fn (ActivityLog $log) => $this->presenter->indexRow($log));

        $logsForModal = collect($items)
            ->mapWithKeys(function (array $row) use ($filteredLogs): array {
                $log = $filteredLogs->firstWhere('id', $row['id']);

                return [$row['id'] => $log ? $this->presenter->detailPayload($log) : []];
            })
            ->all();

        return [
            'logs' => $items,
            'summary' => $this->summarize($rows, $allRows),
            'logs_for_modal' => $logsForModal,
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
        return $this->applyFilters($this->allLogs(), $filters)
            ->map(fn (ActivityLog $log) => $this->presenter->indexRow($log))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, role_slug: string, role_label: string}>
     */
    public function users(): array
    {
        return User::query()
            ->whereIn('id', ActivityLog::query()->distinct()->pluck('user_id'))
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(function (User $user): array {
                $roleSlug = $user->roles->first()?->name ?? 'operations_specialist';

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role_slug' => $roleSlug,
                    'role_label' => UserActivityLogFormData::roles()[$roleSlug] ?? $roleSlug,
                ];
            })
            ->all();
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    private function allLogs(): Collection
    {
        return ActivityLog::query()
            ->with(['user.roles'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  Collection<int, ActivityLog>  $logs
     * @param  array<string, string>  $filters
     * @return Collection<int, ActivityLog>
     */
    private function applyFilters(Collection $logs, array $filters): Collection
    {
        return $logs->filter(function (ActivityLog $log) use ($filters): bool {
            if (($filters['user_id'] ?? 'all') !== 'all' && (int) $log->user_id !== (int) $filters['user_id']) {
                return false;
            }

            if (($filters['role'] ?? 'all') !== 'all') {
                $roleSlug = $log->user?->roles->first()?->name;
                if ($roleSlug !== $filters['role']) {
                    return false;
                }
            }

            if (($filters['activity_type'] ?? 'all') !== 'all' && $log->action !== $filters['activity_type']) {
                return false;
            }

            if (($filters['module'] ?? 'all') !== 'all') {
                $module = UserActivityLogFormData::resolveModule($log->action, $log->subject_type);
                if ($module !== $filters['module']) {
                    return false;
                }
            }

            if (! empty($filters['ip_address']) && ! str_contains($log->ip_address ?? '', trim($filters['ip_address']))) {
                return false;
            }

            if (($filters['date_range'] ?? 'all') !== 'all' && ! $this->matchesDateRange($log, $filters['date_range'])) {
                return false;
            }

            return true;
        })->values();
    }

    private function matchesDateRange(ActivityLog $log, string $range): bool
    {
        $occurred = Carbon::parse($log->created_at);
        $today = Carbon::today();

        return match ($range) {
            'today' => $occurred->isSameDay($today),
            'week' => $occurred->between($today->copy()->startOfWeek(), $today->copy()->endOfWeek()),
            'month' => $occurred->month === $today->month && $occurred->year === $today->year,
            'year' => $occurred->year === $today->year,
            default => true,
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $filtered
     * @param  Collection<int, array<string, mixed>>  $allRows
     * @return array<string, int>
     */
    private function summarize(Collection $filtered, Collection $allRows): array
    {
        $today = Carbon::today();

        return [
            'total' => $filtered->count(),
            'today' => $allRows->filter(fn (array $row) => Carbon::parse($row['occurred_at'])->isSameDay($today))->count(),
            'successful_logins' => $allRows
                ->where('activity_type', 'login')
                ->where('status', 'success')
                ->count(),
            'failed_logins' => $allRows->where('activity_type', 'login_failed')->count(),
            'password_changes' => $allRows->where('activity_type', 'password_changed')->count(),
            'permission_changes' => $allRows
                ->whereIn('activity_type', ['permission_updated', 'role_changed'])
                ->count(),
        ];
    }
}
