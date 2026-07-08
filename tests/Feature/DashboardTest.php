<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Dashboard\Services\DashboardService;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            CitySeeder::class,
            RoleAndPermissionSeeder::class,
        ]);
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
        Business::factory()->count(3)->create(['created_by' => $user->id]);
        Courier::factory()->count(4)->create(['created_by' => $user->id]);
        Agency::factory()->count(2)->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee(number_format(Business::query()->count()));
        $response->assertSee(number_format(Courier::query()->count()));
        $response->assertSee(number_format(Agency::query()->count()));
        $response->assertSee('Son Eklenen İşletmeler');
        $response->assertSee('Son Eklenen Kuryeler');
        $response->assertSee('Kurye Tür Dağılımı');
    }

    public function test_dashboard_service_returns_latest_entities_and_distribution(): void
    {
        $user = User::factory()->create();
        Business::factory()->count(6)->create(['created_by' => $user->id]);
        Courier::factory()->count(6)->create(['created_by' => $user->id]);

        $service = app(DashboardService::class);

        $latestBusinesses = $service->getLatestBusinesses();
        $latestCouriers = $service->getLatestCouriers();
        $distribution = $service->getCourierTypeDistribution();

        $this->assertCount(5, $latestBusinesses);
        $this->assertCount(5, $latestCouriers);
        $this->assertSame(Business::query()->max('id'), $latestBusinesses[0]['id']);
        $this->assertSame(Courier::query()->max('id'), $latestCouriers[0]['id']);
        $this->assertSame(Courier::query()->count(), $distribution['total']);
        $this->assertCount(2, $distribution['items']);
    }

    public function test_dashboard_service_aggregates_live_business_count(): void
    {
        $user = User::factory()->create();
        Business::factory()->count(2)->create(['created_by' => $user->id]);
        Courier::factory()->count(3)->create(['created_by' => $user->id, 'status' => 'active']);
        Courier::factory()->count(1)->create(['created_by' => $user->id, 'status' => 'inactive']);
        Agency::factory()->count(2)->create(['created_by' => $user->id]);

        $stats = app(DashboardService::class)->getStats();

        $this->assertSame(Business::query()->count(), $stats['total_businesses']);
        $this->assertSame(Courier::query()->count(), $stats['total_couriers']);
        $this->assertSame(Agency::query()->count(), $stats['total_agencies']);
        $this->assertSame(Courier::query()->where('status', 'active')->count(), $stats['active_couriers']);
        $this->assertSame(
            Courier::query()->count() - Courier::query()->where('status', 'active')->count(),
            $stats['inactive_couriers']
        );
        $this->assertArrayNotHasKey('monthly_revenue', $stats);
    }
}
