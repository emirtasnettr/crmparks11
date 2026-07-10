<?php

namespace App\Console\Commands;

use App\Modules\Notification\Services\ScheduledReminderService;
use Illuminate\Console\Command;

class SendCollectionRemindersCommand extends Command
{
    protected $signature = 'crmlog:reminders:collections';

    protected $description = 'Tahsilat hatırlatma bildirimlerini kuyruğa alır';

    public function handle(ScheduledReminderService $service): int
    {
        $count = $service->sendCollectionReminders();

        $this->components->info("{$count} tahsilat hatırlatması kuyruğa alındı.");

        return self::SUCCESS;
    }
}
