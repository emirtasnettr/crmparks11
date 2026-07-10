<?php

namespace App\Modules\Notification\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    public function __construct(
        private readonly NotificationPresenter $presenter,
    ) {}

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function index(User $user, array $filters, int $page = 1, int $perPage = 25): array
    {
        $notifications = $this->applyFilters($user->notifications()->latest(), $filters)->get();
        $rows = $notifications->map(fn (DatabaseNotification $notification) => $this->presenter->row($notification))->values();

        $total = $rows->count();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values()->all();

        return [
            'notifications' => $items,
            'summary' => $this->summarize($rows),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * @return array{unread_count: int, items: array<int, array<string, mixed>>}
     */
    public function headerPreview(User $user, int $limit = 5): array
    {
        $notifications = $user->unreadNotifications()->latest()->limit($limit)->get();

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $notifications
                ->map(fn (DatabaseNotification $notification) => $this->presenter->row($notification))
                ->values()
                ->all(),
        ];
    }

    public function markAsRead(User $user, string $id): void
    {
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification === null) {
            abort(404);
        }

        $notification->markAsRead();
    }

    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function delete(User $user, string $id): void
    {
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification === null) {
            abort(404);
        }

        $notification->delete();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Relations\MorphMany<\Illuminate\Notifications\DatabaseNotification, \App\Models\User>  $query
     * @param  array<string, string>  $filters
     */
    private function applyFilters($query, array $filters)
    {
        if (($filters['status'] ?? 'all') === 'unread') {
            $query->whereNull('read_at');
        }

        if (($filters['status'] ?? 'all') === 'read') {
            $query->whereNotNull('read_at');
        }

        if (($filters['type'] ?? 'all') !== 'all') {
            $type = $filters['type'];
            $query->where('data->type', $type);
        }

        if (($filters['date_range'] ?? 'all') !== 'all') {
            $from = match ($filters['date_range']) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                default => null,
            };

            if ($from instanceof Carbon) {
                $query->where('created_at', '>=', $from);
            }
        }

        return $query;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function summarize(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'unread' => $rows->where('is_read', false)->count(),
            'read' => $rows->where('is_read', true)->count(),
            'today' => $rows->filter(function (array $row): bool {
                if (empty($row['created_at'])) {
                    return false;
                }

                return Carbon::parse($row['created_at'])->isToday();
            })->count(),
        ];
    }
}
