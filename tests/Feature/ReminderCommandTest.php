<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\User;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Notification\Jobs\SendSystemNotificationJob;
use App\Modules\Notification\Notifications\SystemNotification;
use App\Modules\Notification\Services\NotificationDispatcher;
use App\Modules\Setting\Contracts\SettingsGroupRepositoryInterface;
use App\Modules\Setting\Data\SettingsDefaults;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);
    }

    public function test_contract_expiry_reminder_command_queues_notifications(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('operations_manager');

        Contract::factory()->create([
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
        ]);

        Artisan::call('crmlog:reminders:contracts');

        $this->assertTrue(
            $manager->fresh()->notifications()->where('data->type', 'contract_expiry')->exists()
        );
    }

    public function test_collection_reminder_command_queues_notifications(): void
    {
        $financeOfficer = User::factory()->create();
        $financeOfficer->assignRole('finance_officer');

        FinanceCollection::factory()->overdue()->create([
            'due_date' => now()->subDay()->toDateString(),
        ]);

        Artisan::call('crmlog:reminders:collections');

        $this->assertTrue(
            $financeOfficer->fresh()->notifications()->where('data->type', 'collection_reminder')->exists()
        );
    }

    public function test_reminder_is_not_sent_twice_on_same_day(): void
    {
        $financeOfficer = User::factory()->create();
        $financeOfficer->assignRole('finance_officer');

        FinanceCollection::factory()->overdue()->create([
            'due_date' => now()->subDay()->toDateString(),
        ]);

        Artisan::call('crmlog:reminders:collections');
        Artisan::call('crmlog:reminders:collections');

        $this->assertSame(
            1,
            $financeOfficer->fresh()->notifications()->where('data->type', 'collection_reminder')->count()
        );
    }

    public function test_notification_dispatcher_queues_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('finance_officer');

        app(NotificationDispatcher::class)->notifyUser(
            $user,
            new SystemNotification(
                type: 'system',
                title: 'Kuyruk Testi',
                message: 'Job kuyruğa alınmalı',
            ),
        );

        Queue::assertPushed(SendSystemNotificationJob::class, function (SendSystemNotificationJob $job) use ($user): bool {
            return $job->userId === $user->id && $job->title === 'Kuyruk Testi';
        });
    }

    public function test_dispatcher_skips_queue_when_system_notifications_disabled(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('finance_officer');

        app(SettingsGroupRepositoryInterface::class)->put('notifications', array_merge(
            SettingsDefaults::notifications(),
            ['system_notifications' => false],
        ));

        app(NotificationDispatcher::class)->notifyUser(
            $user,
            new SystemNotification(
                type: 'system',
                title: 'Engellenmiş',
                message: 'Gönderilmemeli',
            ),
        );

        Queue::assertNothingPushed();
    }
}
