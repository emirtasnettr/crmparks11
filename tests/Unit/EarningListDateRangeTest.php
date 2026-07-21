<?php

namespace Tests\Unit;

use App\Support\EarningListDateRange;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EarningListDateRangeTest extends TestCase
{
    #[Test]
    public function it_defaults_to_last_seven_days_inclusive(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00'));

        $range = EarningListDateRange::resolve(null, null);

        $this->assertSame('2026-07-15', $range['date_from']);
        $this->assertSame('2026-07-21', $range['date_to']);

        Carbon::setTestNow();
    }

    #[Test]
    public function it_swaps_inverted_bounds(): void
    {
        $range = EarningListDateRange::resolve('2026-07-20', '2026-07-10');

        $this->assertSame('2026-07-10', $range['date_from']);
        $this->assertSame('2026-07-20', $range['date_to']);
    }
}
