<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Courier\Data\CourierActivityDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_activities_index_requires_authentication(): void
    {
        $response = $this->get(route('couriers.activities.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_activities_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.activities.index'));

        $response->assertOk();
        $response->assertSee('Hareket Geçmişi');
        $response->assertSee('Kuryeler üzerinde gerçekleştirilen tüm işlemleri görüntüleyin.');
        $response->assertSee('Toplam Hareket');
        $response->assertSee('Bugünkü Hareketler');
        $response->assertSee('Bu Hafta');
        $response->assertSee('Bu Ay');
        $response->assertSee('Kurye Oluşturuldu');
        $response->assertSee('Hakediş Oluşturuldu');
        $response->assertSee('Banka Hesabı Eklendi');
    }

    public function test_activity_logs_have_at_least_one_hundred_records(): void
    {
        $activities = CourierActivityDummyData::all();

        $this->assertCount(110, $activities);
        $this->assertGreaterThanOrEqual(100, count($activities));
    }

    public function test_summary_stats_are_calculated(): void
    {
        $summary = CourierActivityDummyData::summarize(CourierActivityDummyData::all());

        $this->assertEquals(110, $summary['count']);
        $this->assertGreaterThanOrEqual(0, $summary['today']);
        $this->assertGreaterThanOrEqual($summary['today'], $summary['this_week']);
        $this->assertGreaterThanOrEqual($summary['this_week'], $summary['this_month']);
    }

    public function test_activities_can_be_filtered_by_action(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.activities.index', [
            'courier_id' => 1,
            'action' => 'courier_created',
        ]));

        $response->assertOk();
        $response->assertSee('Ahmet Yıldız kuryesi sisteme kaydedildi.');
        $response->assertDontSee('Murat Kaya kuryesi sisteme kaydedildi.');
    }

    public function test_activities_can_be_filtered_by_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $targetUserId = 2;

        $response = $this->actingAs($user)->get(route('couriers.activities.index', [
            'user_id' => $targetUserId,
        ]));

        $response->assertOk();
        $response->assertSee('Elif Demir');
    }
}
