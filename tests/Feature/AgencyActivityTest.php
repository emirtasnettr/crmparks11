<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Data\AgencyActivityDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_agency_activities_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.activities.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_activities_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.activities.index'));

        $response->assertOk();
        $response->assertSee('Hareket Geçmişi');
        $response->assertSee('Acenteler üzerinde gerçekleştirilen tüm işlemleri görüntüleyin.');
        $response->assertSee('Toplam Hareket');
        $response->assertSee('Bugünkü Hareket');
        $response->assertSee('Bu Hafta');
        $response->assertSee('Bu Ay');
        $response->assertSee('Acente Oluşturuldu');
        $response->assertSee('Sözleşme Yenilendi');
        $response->assertSee('Hakediş Oluşturuldu');
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti.');
    }

    public function test_agency_activity_logs_have_at_least_one_hundred_twenty_records(): void
    {
        $activities = AgencyActivityDummyData::all();

        $this->assertCount(125, $activities);
        $this->assertGreaterThanOrEqual(120, count($activities));
    }

    public function test_summary_stats_are_calculated(): void
    {
        $summary = AgencyActivityDummyData::summarize(AgencyActivityDummyData::all());

        $this->assertEquals(125, $summary['count']);
        $this->assertGreaterThanOrEqual(0, $summary['today']);
        $this->assertGreaterThanOrEqual($summary['today'], $summary['this_week']);
        $this->assertGreaterThanOrEqual($summary['this_week'], $summary['this_month']);
    }

    public function test_agency_activities_can_be_filtered_by_action(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.activities.index', [
            'agency_id' => 1,
            'action' => 'agency_created',
        ]));

        $response->assertOk();
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti. acentesi sisteme kaydedildi.');
    }

    public function test_agency_activities_can_be_filtered_by_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.activities.index', [
            'user_id' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('Elif Demir');
    }
}
