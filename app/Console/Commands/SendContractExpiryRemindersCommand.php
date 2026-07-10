<?php

namespace App\Console\Commands;

use App\Modules\Notification\Services\ScheduledReminderService;
use Illuminate\Console\Command;

class SendContractExpiryRemindersCommand extends Command
{
    protected $signature = 'crmlog:reminders:contracts';

    protected $description = 'Sözleşme bitiş hatırlatma bildirimlerini kuyruğa alır';

    public function handle(ScheduledReminderService $service): int
    {
        $count = $service->sendContractExpiryReminders();

        $this->components->info("{$count} sözleşme hatırlatması kuyruğa alındı.");

        return self::SUCCESS;
    }
}
