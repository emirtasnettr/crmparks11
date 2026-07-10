<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Notification\Notifications\SystemNotification;
use App\Modules\Notification\Services\NotificationDispatcher;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\Setting\Contracts\SettingsGroupRepositoryInterface;
use App\Modules\Setting\Data\SettingsDefaults;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    public function test_user_with_permission_can_view_notifications_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('finance_officer');

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Bildirimler');
        $response->assertSee('Sistem bildirimlerinizi görüntüleyin ve yönetin.');
    }

    public function test_user_without_permission_cannot_view_notifications_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_staff');

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertForbidden();
    }

    public function test_dispatcher_respects_disabled_notification_settings(): void
    {
        NotificationFacade::fake();

        $user = User::factory()->create();
        $user->assignRole('finance_officer');

        $dispatcher = app(NotificationDispatcher::class);

        app(SettingsGroupRepositoryInterface::class)->put('notifications', array_merge(
            SettingsDefaults::notifications(),
            ['system_notifications' => false],
        ));

        $dispatcher->notifyUser(
            $user,
            new SystemNotification(
                type: 'earning_approved',
                title: 'Test',
                message: 'Test mesajı',
            ),
        );

        NotificationFacade::assertNothingSent();
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole('finance_officer');

        $user->notify(new SystemNotification(
            type: 'system',
            title: 'Test Bildirimi',
            message: 'Okunacak bildirim',
        ));

        $notification = $user->unreadNotifications()->firstOrFail();

        $response = $this->actingAs($user)->patch(route('notifications.mark-read', $notification->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole('finance_officer');

        $user->notify(new SystemNotification(type: 'system', title: 'Bir', message: 'Mesaj 1'));
        $user->notify(new SystemNotification(type: 'system', title: 'İki', message: 'Mesaj 2'));

        $response = $this->actingAs($user)->post(route('notifications.mark-all-read'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_user_can_delete_notification(): void
    {
        $user = User::factory()->create();
        $user->assignRole('finance_officer');

        $user->notify(new SystemNotification(type: 'system', title: 'Silinecek', message: 'Mesaj'));

        $notification = $user->notifications()->firstOrFail();

        $response = $this->actingAs($user)->delete(route('notifications.destroy', $notification->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_header_preview_returns_unread_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('finance_officer');

        $user->notify(new SystemNotification(type: 'system', title: 'Önizleme', message: 'Header test'));

        $preview = app(NotificationService::class)->headerPreview($user);

        $this->assertSame(1, $preview['unread_count']);
        $this->assertCount(1, $preview['items']);
        $this->assertSame('Önizleme', $preview['items'][0]['title']);
    }
}
