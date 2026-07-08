<?php

namespace Tests\Unit;

use App\Modules\Business\Data\BusinessDummyData;
use App\Modules\Business\Data\BusinessOverviewStats;
use App\Modules\Business\Services\BusinessProfileStore;
use Carbon\Carbon;
use Tests\TestCase;

class BusinessOverviewStatsTest extends TestCase
{
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

    public function test_overview_stats_use_stored_unit_prices_when_available(): void
    {
        BusinessProfileStore::put(1, [
            'customer_price' => '62.50',
            'courier_price' => '41.25',
        ]);

        $stats = BusinessOverviewStats::forBusiness(
            1,
            Carbon::parse('2026-07-02'),
            Carbon::parse('2026-07-08'),
        );

        $this->assertSame(62.5, $stats['received_per_package']);
        $this->assertSame(41.25, $stats['courier_per_package']);
        $this->assertSame(21.25, $stats['net_per_package']);

        BusinessProfileStore::forget(1);
    }

    public function test_unit_prices_fall_back_to_defaults_without_profile(): void
    {
        BusinessProfileStore::forget(1);

        $unitPrices = BusinessDummyData::unitPrices(1);

        $this->assertFalse($unitPrices['from_profile']);
        $this->assertSame(45.0, $unitPrices['revenue_unit']);
        $this->assertSame(32.0, $unitPrices['courier_unit']);
    }
}
