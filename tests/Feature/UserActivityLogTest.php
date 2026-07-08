<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\User\Data\UserActivityLogDummyData;
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

    public function test_dummy_data_has_at_least_three_hundred_logs(): void
    {
        $logs = UserActivityLogDummyData::all();

        $this->assertGreaterThanOrEqual(300, count($logs));
        $this->assertCount(320, $logs);
    }

    public function test_logs_contain_spatie_compatible_fields(): void
    {
        $log = UserActivityLogDummyData::all()[0];

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

        $response = $this->actingAs($user)->get(route('users.activity-log.index', ['activity_type' => 'login_failed']));

        $response->assertOk();
        $response->assertSee('Başarısız Giriş');
    }

    public function test_export_payload_is_ready(): void
    {
        $payload = UserActivityLogDummyData::exportPayload([], 'xlsx');

        $this->assertSame('xlsx', $payload['format']);
        $this->assertStringContainsString('crmlog-aktivite-kayitlari', $payload['filename']);
        $this->assertNotEmpty($payload['columns']);
    }
}
