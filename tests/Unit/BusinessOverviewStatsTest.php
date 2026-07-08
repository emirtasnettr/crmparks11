<?php

namespace Tests\Unit;

use App\Models\PricingModelType;
use App\Models\User;
use App\Modules\Business\Models\Business;
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
}
