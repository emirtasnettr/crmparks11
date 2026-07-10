<?php

namespace App\Modules\Notification\Services;

use App\Models\User;
use App\Modules\Notification\Jobs\SendSystemNotificationJob;
use App\Modules\Notification\Notifications\SystemNotification;
use App\Modules\Notification\Data\NotificationFormData;
use App\Modules\Setting\Services\SettingsManager;
use Illuminate\Support\Collection;

class NotificationDispatcher
{
    public function __construct(
        private readonly SettingsManager $settings,
    ) {}

    public function notifyUser(User $user, SystemNotification $notification): void
    {
        if (! $this->shouldSend($notification->type)) {
            return;
        }

        $this->queueNotification($user, $notification);
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function notifyRoles(array $roles, SystemNotification $notification, ?User $except = null): int
    {
        if (! $this->shouldSend($notification->type)) {
            return 0;
        }

        $count = 0;

        $this->usersForRoles($roles, $except)->each(function (User $user) use ($notification, &$count): void {
            $this->queueNotification($user, $notification);
            $count++;
        });

        return $count;
    }

    public function sendNow(User $user, SystemNotification $notification): void
    {
        if (! $this->shouldSend($notification->type)) {
            return;
        }

        $user->notify($notification);
    }

    public function isTypeEnabled(string $type): bool
    {
        $settingKey = NotificationFormData::settingKeyForType($type);

        if ($settingKey === null) {
            return true;
        }

        return (bool) ($this->notificationSettings()[$settingKey] ?? false);
    }

    public function isSystemChannelEnabled(): bool
    {
        return (bool) ($this->notificationSettings()['system_notifications'] ?? true);
    }

    private function shouldSend(string $type): bool
    {
        return $this->isTypeEnabled($type) && $this->isSystemChannelEnabled();
    }

    private function queueNotification(User $user, SystemNotification $notification): void
    {
        SendSystemNotificationJob::dispatch(
            $user->id,
            $notification->type,
            $notification->title,
            $notification->message,
            $notification->actionUrl,
            $notification->meta,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationSettings(): array
    {
        return $this->settings->group('notifications')->all();
    }

    /**
     * @param  array<int, string>  $roles
     * @return Collection<int, User>
     */
    private function usersForRoles(array $roles, ?User $except = null): Collection
    {
        return User::query()
            ->role($roles)
            ->when($except !== null, fn ($query) => $query->where('id', '!=', $except->id))
            ->get();
    }
}
