<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceRevenue;
use App\Modules\Finance\Services\FinanceActivityLogService;
use Carbon\Carbon;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceActivityLogTest extends TestCase
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

    public function test_activity_log_requires_authentication(): void
    {
        $response = $this->get(route('finance.activity-log.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_activity_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $revenue = FinanceRevenue::factory()->create();
        $collection = FinanceCollection::factory()->create();

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'revenue_created',
            'subject_type' => FinanceRevenue::class,
            'subject_id' => $revenue->id,
            'description' => "{$revenue->reference} gelir kaydı oluşturuldu.",
            'new_values' => ['reference' => $revenue->reference],
        ]);

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'collection_created',
            'subject_type' => FinanceCollection::class,
            'subject_id' => $collection->id,
            'description' => "{$collection->reference} tahsilat kaydı oluşturuldu.",
            'new_values' => ['reference' => $collection->reference],
        ]);

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

    public function test_operations_specialist_cannot_view_activity_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $response = $this->actingAs($user)->get(route('finance.activity-log.index'));

        $response->assertForbidden();
    }

    public function test_finance_activity_logs_are_loaded_from_database(): void
    {
        $user = User::factory()->create();
        $revenue = FinanceRevenue::factory()->create();

        ActivityLog::factory()->count(3)->create([
            'user_id' => $user->id,
            'action' => 'revenue_created',
            'subject_type' => FinanceRevenue::class,
            'subject_id' => $revenue->id,
        ]);

        $logs = app(FinanceActivityLogService::class)->filter(['date_range' => 'all']);

        $this->assertCount(3, $logs);
    }

    public function test_logs_contain_spatie_compatible_fields(): void
    {
        $user = User::factory()->create();
        $revenue = FinanceRevenue::factory()->create();

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'revenue_created',
            'subject_type' => FinanceRevenue::class,
            'subject_id' => $revenue->id,
            'old_values' => ['status' => 'draft'],
            'new_values' => ['status' => 'active', 'reference' => $revenue->reference],
        ]);

        $log = app(FinanceActivityLogService::class)->filter(['date_range' => 'all'])[0];

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

        $collection = FinanceCollection::factory()->create();

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'collection_created',
            'subject_type' => FinanceCollection::class,
            'subject_id' => $collection->id,
            'description' => "{$collection->reference} tahsilat kaydı oluşturuldu.",
            'new_values' => ['reference' => $collection->reference],
        ]);

        $response = $this->actingAs($user)->get(route('finance.activity-log.index', [
            'module' => 'collections',
        ]));

        $response->assertOk();
        $response->assertSee('Tahsilatlar');
        $response->assertSee('hareket kaydı listeleniyor');
    }

    public function test_critical_actions_are_tracked(): void
    {
        $user = User::factory()->create();
        $revenue = FinanceRevenue::factory()->create();

        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'revenue_created',
            'subject_type' => FinanceRevenue::class,
            'subject_id' => $revenue->id,
            'new_values' => ['status' => 'cancelled', 'reference' => $revenue->reference],
        ]);

        $analysis = app(FinanceActivityLogService::class)->analyze(['date_range' => 'all']);

        $this->assertGreaterThan(0, $analysis['summary']['critical']);
    }
}
