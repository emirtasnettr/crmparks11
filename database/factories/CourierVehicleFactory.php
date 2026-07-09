<?php

namespace Database\Factories;

use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierVehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CourierVehicle> */
class CourierVehicleFactory extends Factory
{
    protected $model = CourierVehicle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'courier_id' => Courier::factory(),
            'vehicle_type' => 'motorcycle',
            'plate' => fake()->numerify('34 ## ####'),
            'brand' => fake()->randomElement(['Honda', 'Yamaha', 'Kuba']),
            'model' => fake()->word(),
            'model_year' => fake()->numberBetween(2018, 2025),
            'color' => fake()->safeColorName(),
            'license_number' => fake()->bothify('RUH-##-??-####'),
            'insurance_policy_number' => fake()->bothify('SIG-####-###'),
            'insurance_expiry_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'registered_at' => now()->subYear()->toDateString(),
            'notes' => null,
        ];
    }
}
