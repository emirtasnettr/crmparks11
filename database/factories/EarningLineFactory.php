<?php

namespace Database\Factories;

use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EarningLine> */
class EarningLineFactory extends Factory
{
    protected $model = EarningLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $packageCount = fake()->numberBetween(100, 2000);
        $revenueUnit = 45.0;
        $courierUnit = 38.0;
        $revenueTotal = round($packageCount * $revenueUnit, 2);
        $courierTotal = round($packageCount * $courierUnit, 2);
        $profit = round($revenueTotal - $courierTotal, 2);

        return [
            'business_id' => Business::factory(),
            'courier_id' => Courier::factory(),
            'earning_type' => 'package_based',
            'pricing_model' => 'per_package',
            'period_month' => (int) now()->format('n'),
            'period_year' => (int) now()->format('Y'),
            'work_date' => now()->toDateString(),
            'package_count' => $packageCount,
            'worked_hours' => 0,
            'revenue_unit_price' => $revenueUnit,
            'revenue_total' => $revenueTotal,
            'courier_unit_price' => $courierUnit,
            'courier_total' => $courierTotal,
            'agency_payment' => 0,
            'extra_payment' => 0,
            'extra_expense' => 0,
            'deduction' => 0,
            'net_courier_payment' => $courierTotal,
            'profit' => $profit,
            'status_id' => EarningStatus::query()->where('code', 'draft')->value('id'),
            'created_by' => User::factory(),
        ];
    }
}
