<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\User\Services\UserActivityLogService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_activity_log_requires_authentication(): void
    {
        $response = $this->get(route('users.activity-log.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_activity_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        ActivityLog::factory()->for($user)->login()->create([
            'ip_address' => '185.24.10.42',
        ]);
        ActivityLog::factory()->for($user)->permissionUpdated()->create();

        $response = $this->actingAs($user)->get(route('users.activity-log.index'));

        $response->assertOk();
        $response->assertSee('Aktivite Kayıtları');
        $response->assertSee('Sistemdeki tüm kullanıcı aktivitelerini görüntüleyin.');
        $response->assertSee('Toplam Aktivite');
        $response->assertSee('Bugünkü Aktivite');
        $response->assertSee('Başarılı Giriş');
        $response->assertSee('Başarısız Giriş');
        $response->assertSee('Şifre Değişiklikleri');
        $response->assertSee('Yetki Değişiklikleri');
        $response->assertSee('aktivite kaydı listeleniyor');
        $response->assertSee('salt okunurdur');
        $response->assertSee('Giriş Yapıldı');
        $response->assertSee('Yetki Güncellendi');
        $response->assertSee('Detayları Görüntüle');
        $response->assertSee('Kullanıcı Profiline Git');
        $response->assertSee('Excel');
        $response->assertSee('PDF');
    }

    public function test_general_manager_can_view_activity_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('general_manager');

        ActivityLog::factory()->for($user)->login()->create();

        $response = $this->actingAs($user)->get(route('users.activity-log.index'));

        $response->assertOk();
        $response->assertSee('Aktivite Kayıtları');
    }

    public function test_operations_manager_cannot_view_activity_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('users.activity-log.index'));

        $response->assertForbidden();
    }

    public function test_logs_are_loaded_from_database(): void
    {
        $user = User::factory()->create();
        ActivityLog::factory()->count(5)->for($user)->create();

        $logs = app(UserActivityLogService::class)->exportRows([]);

        $this->assertGreaterThanOrEqual(5, count($logs));
    }

    public function test_logs_contain_spatie_compatible_fields(): void
    {
        $user = User::factory()->create();
        ActivityLog::factory()->for($user)->login()->create();

        $log = app(UserActivityLogService::class)->exportRows([])[0];

        $this->assertArrayHasKey('log_name', $log);
        $this->assertArrayHasKey('event', $log);
        $this->assertArrayHasKey('subject_type', $log);
        $this->assertArrayHasKey('subject_id', $log);
        $this->assertArrayHasKey('causer_type', $log);
        $this->assertArrayHasKey('causer_id', $log);
        $this->assertArrayHasKey('properties', $log);
        $this->assertArrayHasKey('old', $log['properties']);
        $this->assertArrayHasKey('attributes', $log['properties']);
    }

    public function test_logs_can_be_filtered_by_activity_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        ActivityLog::factory()->for($user)->login()->create();
        ActivityLog::factory()->for($user)->loginFailed()->create([
            'ip_address' => '10.0.0.99',
        ]);

        $response = $this->actingAs($user)->get(route('users.activity-log.index', ['activity_type' => 'login_failed']));

        $response->assertOk();
        $response->assertSee('Başarısız Giriş');
        $response->assertSee('10.0.0.99');
    }

    public function test_export_returns_filtered_rows(): void
    {
        $user = User::factory()->create();
        ActivityLog::factory()->for($user)->login()->create();

        $rows = app(UserActivityLogService::class)->exportRows([]);

        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('user_name', $rows[0]);
        $this->assertArrayHasKey('activity_type_label', $rows[0]);
    }
}
