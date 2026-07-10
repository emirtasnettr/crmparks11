<?php

namespace App\Modules\Notification\Services;

use App\Models\User;
use App\Modules\Notification\Notifications\SystemNotification;

class UserNotificationService
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function notifyCreated(User $user, User $actor): void
    {
        $this->dispatcher->notifyRoles(
            ['super_admin', 'general_manager'],
            new SystemNotification(
                type: 'user_created',
                title: 'Yeni Kullanıcı Oluşturuldu',
                message: "{$user->name} kullanıcı hesabı oluşturuldu.",
                actionUrl: route('users.show', $user->id),
                meta: [
                    'user_id' => $user->id,
                    'actor_id' => $actor->id,
                ],
            ),
            except: $actor,
        );
    }
}
