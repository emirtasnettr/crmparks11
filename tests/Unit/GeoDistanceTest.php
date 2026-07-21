<?php

namespace Tests\Unit;

use App\Support\GeoDistance;
use PHPUnit\Framework\TestCase;

class GeoDistanceTest extends TestCase
{
    public function test_same_point_is_zero_meters(): void
    {
        $this->assertEqualsWithDelta(0.0, GeoDistance::metersBetween(41.0082, 28.9784, 41.0082, 28.9784), 0.01);
    }

    public function test_nearby_points_are_within_300_meters(): void
    {
        // ~111 m kuzey
        $distance = GeoDistance::metersBetween(41.0082, 28.9784, 41.0092, 28.9784);
        $this->assertGreaterThan(90, $distance);
        $this->assertLessThan(150, $distance);
    }
}
