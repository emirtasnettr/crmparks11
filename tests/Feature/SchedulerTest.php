<?php

namespace Tests\Feature;

use Tests\TestCase;

class SchedulerTest extends TestCase
{
    public function test_reminder_commands_are_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->assertSuccessful()
            ->expectsOutputToContain('crmlog:reminders:contracts')
            ->expectsOutputToContain('crmlog:reminders:documents')
            ->expectsOutputToContain('crmlog:reminders:collections')
            ->expectsOutputToContain('crmlog:reminders:payments');
    }
}
