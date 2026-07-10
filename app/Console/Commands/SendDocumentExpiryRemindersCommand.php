<?php

namespace App\Console\Commands;

use App\Modules\Notification\Services\ScheduledReminderService;
use Illuminate\Console\Command;

class SendDocumentExpiryRemindersCommand extends Command
{
    protected $signature = 'crmlog:reminders:documents';

    protected $description = 'Evrak süresi hatırlatma bildirimlerini kuyruğa alır';

    public function handle(ScheduledReminderService $service): int
    {
        $count = $service->sendDocumentExpiryReminders();

        $this->components->info("{$count} evrak hatırlatması kuyruğa alındı.");

        return self::SUCCESS;
    }
}
