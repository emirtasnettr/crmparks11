<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Data\FinanceActivityLogDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_activity_log_requires_authentication(): void
    {
        $response = $this->get(route('finance.activity-log.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_activity_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.activity-log.index'));

        $response->assertOk();
        $response->assertSee('Finans Hareket Geçmişi');
        $response->assertSee('Finans modülünde gerçekleştirilen tüm işlemleri görüntüleyin.');
        $response->assertSee('Toplam Hareket');
        $response->assertSee('Bugünkü Hareket');
        $response->assertSee('Kritik İşlem Sayısı');
        $response->assertSee('Kayıt Oluşturuldu');
        $response->assertSee('Tahsilat Yapıldı');
        $response->assertSee('hareket kaydı listeleniyor');
        $response->assertSee('salt okunurdur');
    }

    public function test_general_manager_can_view_activity_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('general_manager');

        $response = $this->actingAs($user)->get(route('finance.activity-log.index'));

        $response->assertOk();
        $response->assertSee('Finans Hareket Geçmişi');
    }

    public function test_operations_manager_cannot_view_activity_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('finance.activity-log.index'));

        $response->assertForbidden();
    }

    public function test_dummy_data_has_at_least_two_hundred_logs(): void
    {
        $logs = FinanceActivityLogDummyData::all();

        $this->assertGreaterThanOrEqual(200, count($logs));
        $this->assertCount(210, $logs);
    }

    public function test_logs_contain_spatie_compatible_fields(): void
    {
        $log = FinanceActivityLogDummyData::all()[0];

        $this->assertArrayHasKey('log_name', $log);
        $this->assertArrayHasKey('subject_type', $log);
        $this->assertArrayHasKey('subject_id', $log);
        $this->assertArrayHasKey('properties', $log);
        $this->assertArrayHasKey('old', $log['properties']);
        $this->assertArrayHasKey('attributes', $log['properties']);
    }

    public function test_activity_log_can_be_filtered_by_module(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.activity-log.index', [
            'module' => 'collections',
        ]));

        $response->assertOk();
        $response->assertSee('Tahsilatlar');
        $response->assertSee('hareket kaydı listeleniyor');
    }

    public function test_critical_actions_are_tracked(): void
    {
        $analysis = FinanceActivityLogDummyData::analyze(['date_range' => 'all']);

        $this->assertGreaterThan(0, $analysis['summary']['critical']);
    }
}
