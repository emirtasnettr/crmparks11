<?php

namespace App\Modules\Notification\Services;

use App\Models\User;
use App\Modules\Notification\Data\NotificationFormData;
use App\Modules\Notification\Notifications\SystemNotification;
use App\Modules\Setting\Services\SettingsManager;
use Illuminate\Support\Collection;

class NotificationDispatcher
{
    public function __construct(
        private readonly SettingsManager $settings,
    ) {}

    public function notifyUser(User $user, SystemNotification $notification): void
    {
        if (! $this->isTypeEnabled($notification->type)) {
            return;
        }

        if (! $this->isSystemChannelEnabled()) {
            return;
        }

        $user->notify($notification);
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function notifyRoles(array $roles, SystemNotification $notification, ?User $except = null): void
    {
        if (! $this->isTypeEnabled($notification->type) || ! $this->isSystemChannelEnabled()) {
            return;
        }

        $this->usersForRoles($roles, $except)->each(
            fn (User $user) => $user->notify($notification),
        );
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
