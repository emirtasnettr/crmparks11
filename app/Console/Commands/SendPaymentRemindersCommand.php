<?php

namespace App\Console\Commands;

use App\Modules\Notification\Services\ScheduledReminderService;
use Illuminate\Console\Command;

class SendPaymentRemindersCommand extends Command
{
    protected $signature = 'crmlog:reminders:payments';

    protected $description = 'Ödeme hatırlatma bildirimlerini kuyruğa alır';

    public function handle(ScheduledReminderService $service): int
    {
        $count = $service->sendPaymentReminders();

        $this->components->info("{$count} ödeme hatırlatması kuyruğa alındı.");

        return self::SUCCESS;
    }
}
