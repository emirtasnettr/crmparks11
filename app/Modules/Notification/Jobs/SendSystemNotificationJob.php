<?php

namespace App\Modules\Notification\Jobs;

use App\Models\User;
use App\Modules\Notification\Notifications\SystemNotification;
use App\Modules\Notification\Services\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSystemNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $actionUrl = null,
        public readonly array $meta = [],
    ) {}

    public function handle(NotificationDispatcher $dispatcher): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            return;
        }

        $dispatcher->sendNow(
            $user,
            new SystemNotification(
                type: $this->type,
                title: $this->title,
                message: $this->message,
                actionUrl: $this->actionUrl,
                meta: $this->meta,
            ),
        );
    }
}
