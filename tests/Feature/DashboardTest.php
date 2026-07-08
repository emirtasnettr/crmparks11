<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Data\AgencyDummyData;
use App\Modules\Business\Data\BusinessDummyData;
use App\Modules\Courier\Data\CourierDummyData;
use App\Modules\Dashboard\Services\DashboardService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_dashboard_with_live_stats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee(number_format(count(BusinessDummyData::all())));
        $response->assertSee(number_format(CourierDummyData::summary([])['total']));
        $response->assertSee(number_format(AgencyDummyData::summary([])['total']));
        $response->assertSee('Son Eklenen İşletmeler');
        $response->assertSee('Son Eklenen Kuryeler');
        $response->assertSee('Kurye Tür Dağılımı');
    }

    public function test_dashboard_service_returns_latest_entities_and_distribution(): void
    {
        $service = app(DashboardService::class);

        $latestBusinesses = $service->getLatestBusinesses();
        $latestCouriers = $service->getLatestCouriers();
        $distribution = $service->getCourierTypeDistribution();

        $this->assertCount(5, $latestBusinesses);
        $this->assertCount(5, $latestCouriers);
        $this->assertSame(8, $latestBusinesses[0]['id']);
        $this->assertSame(32, $latestCouriers[0]['id']);
        $this->assertSame(CourierDummyData::summary([])['total'], $distribution['total']);
        $this->assertCount(2, $distribution['items']);
    }

    public function test_dashboard_service_aggregates_dummy_data(): void
    {
        $stats = app(DashboardService::class)->getStats();
        $courierSummary = CourierDummyData::summary([]);

        $this->assertSame(count(BusinessDummyData::all()), $stats['total_businesses']);
        $this->assertSame($courierSummary['total'], $stats['total_couriers']);
        $this->assertSame(AgencyDummyData::summary([])['total'], $stats['total_agencies']);
        $this->assertSame($courierSummary['active'], $stats['active_couriers']);
        $this->assertSame($courierSummary['total'] - $courierSummary['active'], $stats['inactive_couriers']);
        $this->assertArrayNotHasKey('monthly_revenue', $stats);
    }
}
