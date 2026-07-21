<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('crmlog:reminders:contracts')->dailyAt('08:00');
Schedule::command('crmlog:reminders:documents')->dailyAt('08:15');
Schedule::command('crmlog:reminders:collections')->dailyAt('09:00');
Schedule::command('crmlog:reminders:payments')->dailyAt('09:15');
Schedule::command('crmlog:shifts:auto-end')->everyFiveMinutes();
Schedule::command('crmlog:earnings:sync-from-attendance')->hourly();
