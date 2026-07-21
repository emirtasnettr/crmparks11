<?php

namespace Tests\Unit;

use App\Support\EarningCalculator;
use PHPUnit\Framework\TestCase;

class EarningCalculatorTest extends TestCase
{
    public function test_hourly_manual_form_uses_hours_times_rates(): void
    {
        $amounts = EarningCalculator::fromForm([
            'pricing_model' => 'hourly',
            'worked_hours' => 8,
            'revenue_unit_price' => 225,
            'courier_unit_price' => 150,
            'extra_income' => 100,
            'extra_expense' => 50,
            'deduction' => 25,
        ], false);

        $this->assertSame('hourly', $amounts['earning_type']);
        $this->assertEquals(8.0, $amounts['worked_hours']);
        $this->assertEquals(1800.0, $amounts['revenue_total']);
        $this->assertEquals(1200.0, $amounts['courier_total']);
        $this->assertEquals(1275.0, $amounts['net_courier_payment']);
        $this->assertEquals(625.0, $amounts['profit']);
    }

    public function test_hourly_with_worked_hours_uses_unit_rates_when_totals_missing(): void
    {
        $amounts = EarningCalculator::fromForm([
            'pricing_model' => 'hourly',
            'worked_hours' => 8,
            'revenue_unit_price' => 200,
            'courier_unit_price' => 150,
        ], false);

        $this->assertSame('hourly', $amounts['earning_type']);
        $this->assertEquals(1600.0, $amounts['revenue_total']);
        $this->assertEquals(1200.0, $amounts['courier_total']);
    }

    public function test_hourly_falls_back_to_direct_totals_without_hours(): void
    {
        $amounts = EarningCalculator::fromForm([
            'pricing_model' => 'hourly',
            'revenue_total' => 1800,
            'courier_payment' => 1200,
        ], false);

        $this->assertEquals(1800.0, $amounts['revenue_total']);
        $this->assertEquals(1200.0, $amounts['courier_total']);
    }

    public function test_monthly_fixed_uses_direct_totals(): void
    {
        $amounts = EarningCalculator::fromForm([
            'pricing_model' => 'monthly_fixed',
            'revenue_total' => 15000,
            'courier_payment' => 12000,
        ], false);

        $this->assertSame('fixed_period', $amounts['earning_type']);
        $this->assertEquals(15000.0, $amounts['revenue_total']);
        $this->assertEquals(12000.0, $amounts['courier_total']);
        $this->assertEquals(3000.0, $amounts['profit']);
    }
}
