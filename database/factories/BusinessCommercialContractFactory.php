<?php

namespace Database\Factories;

use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCommercialContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessCommercialContract>
 */
class BusinessCommercialContractFactory extends Factory
{
    protected $model = BusinessCommercialContract::class;

    public function definition(): array
    {
        $businessAmount = fake()->randomFloat(2, 40, 120);
        $courierAmount = round($businessAmount * 0.7, 2);

        return [
            'business_id' => Business::factory(),
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'work_type' => BusinessCommercialContract::WORK_HOURLY,
            'business_amount' => $businessAmount,
            'courier_amount' => $courierAmount,
            'net_profit' => round($businessAmount - $courierAmount, 2),
            'guaranteed_hourly_package_fee' => null,
            'payment_period' => BusinessCommercialContract::PERIOD_MONTHLY,
            'status' => BusinessCommercialContract::STATUS_ACTIVE,
            'supersedes_id' => null,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function perPackage(): static
    {
        return $this->state(fn () => [
            'work_type' => BusinessCommercialContract::WORK_PER_PACKAGE,
        ]);
    }

    public function hourly(): static
    {
        return $this->state(fn () => [
            'work_type' => BusinessCommercialContract::WORK_HOURLY,
            'guaranteed_hourly_package_fee' => null,
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'status' => BusinessCommercialContract::STATUS_ENDED,
            'end_date' => now()->toDateString(),
        ]);
    }
}
