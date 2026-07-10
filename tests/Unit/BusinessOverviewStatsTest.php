<?php

namespace Tests\Unit;

use App\Models\EarningLine;
use App\Models\PricingModelType;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Business\Models\BusinessPricing;
use App\Modules\Business\Data\BusinessOverviewStats;
use App\Modules\Business\Services\BusinessPresenter;
use Carbon\Carbon;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessOverviewStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            CitySeeder::class,
        ]);
    }

    public function test_default_date_range_is_last_seven_days_including_today(): void
    {
        Carbon::setTestNow('2026-07-08 12:00:00');

        $range = BusinessOverviewStats::resolveDateRange(null, null);

        $this->assertSame('2026-07-02', $range['start_date']);
        $this->assertSame('2026-07-08', $range['end_date']);

        Carbon::setTestNow();
    }

    public function test_date_range_swaps_when_start_is_after_end(): void
    {
        $range = BusinessOverviewStats::resolveDateRange('2026-07-10', '2026-07-01');

        $this->assertSame('2026-07-01', $range['start_date']);
        $this->assertSame('2026-07-10', $range['end_date']);
    }

    public function test_overview_stats_use_database_unit_prices_when_available(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create(['created_by' => $user->id]);
        $pricingModel = PricingModelType::query()->where('code', 'per_package')->firstOrFail();

        $business->pricings()->delete();
        BusinessPricing::query()->create([
            'business_id' => $business->id,
            'pricing_model_type_id' => $pricingModel->id,
            'customer_unit_price' => 62.50,
            'courier_unit_price' => 41.25,
            'effective_from' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $stats = BusinessOverviewStats::forBusiness(
            $business->id,
            Carbon::parse('2026-07-02'),
            Carbon::parse('2026-07-08'),
        );

        $this->assertSame(62.5, $stats['received_per_package']);
        $this->assertSame(41.25, $stats['courier_per_package']);
        $this->assertSame(21.25, $stats['net_per_package']);
    }

    public function test_unit_prices_fall_back_to_zero_without_active_pricing(): void
    {
        $business = Business::factory()->create();
        $business->pricings()->delete();
        $business->load('activePricing');

        $unitPrices = app(BusinessPresenter::class)->unitPrices($business);

        $this->assertFalse($unitPrices['from_profile']);
        $this->assertSame(0.0, $unitPrices['revenue_unit']);
        $this->assertSame(0.0, $unitPrices['courier_unit']);
    }

    public function test_active_couriers_are_counted_from_assignments(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create(['created_by' => $user->id]);
        $firstCourier = \App\Modules\Courier\Models\Courier::factory()->create(['created_by' => $user->id]);
        $secondCourier = \App\Modules\Courier\Models\Courier::factory()->create(['created_by' => $user->id]);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $firstCourier->id,
            'start_date' => '2026-07-01',
            'end_date' => null,
            'status' => 'active',
            'assigned_by' => $user->id,
        ]);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $secondCourier->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'inactive',
            'assigned_by' => $user->id,
        ]);

        $stats = BusinessOverviewStats::forBusiness(
            $business->id,
            Carbon::parse('2026-07-02'),
            Carbon::parse('2026-07-08'),
        );

        $this->assertSame(1, $stats['active_couriers']);
    }

    public function test_terminated_assignments_are_excluded_from_active_courier_count(): void
    {
        Carbon::setTestNow('2026-07-08 12:00:00');

        $user = User::factory()->create();
        $business = Business::factory()->create(['created_by' => $user->id]);
        $activeCourier = \App\Modules\Courier\Models\Courier::factory()->create(['created_by' => $user->id]);
        $terminatedCourier = \App\Modules\Courier\Models\Courier::factory()->create(['created_by' => $user->id]);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $activeCourier->id,
            'start_date' => '2026-07-01',
            'end_date' => null,
            'status' => 'active',
            'assigned_by' => $user->id,
        ]);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $terminatedCourier->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-07',
            'status' => 'inactive',
            'assigned_by' => $user->id,
        ]);

        $stats = BusinessOverviewStats::forBusiness(
            $business->id,
            Carbon::parse('2026-07-02'),
            Carbon::parse('2026-07-08'),
        );

        $this->assertSame(1, $stats['active_couriers']);
        $this->assertSame(1, $business->fresh()->activeCourierCount());

        Carbon::setTestNow();
    }

    public function test_overview_stats_aggregate_earning_lines_in_period(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create(['created_by' => $user->id]);

        EarningLine::factory()->create([
            'business_id' => $business->id,
            'period_month' => 7,
            'period_year' => 2026,
            'package_count' => 100,
            'revenue_total' => 5000,
            'courier_total' => 3500,
            'created_by' => $user->id,
        ]);

        EarningLine::factory()->create([
            'business_id' => $business->id,
            'period_month' => 7,
            'period_year' => 2026,
            'package_count' => 50,
            'revenue_total' => 2500,
            'courier_total' => 1750,
            'created_by' => $user->id,
        ]);

        $stats = BusinessOverviewStats::forBusiness(
            $business->id,
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-07-15'),
        );

        $this->assertSame(150, $stats['total_packages']);
        $this->assertSame(50.0, $stats['received_per_package']);
        $this->assertSame(35.0, $stats['courier_per_package']);
        $this->assertSame(15.0, $stats['net_per_package']);
    }
}
