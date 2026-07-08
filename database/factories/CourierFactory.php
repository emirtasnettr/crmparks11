<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VehicleType;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Courier> */
class CourierFactory extends Factory
{
    protected $model = Courier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $firstName.' '.$lastName,
            'phone' => '05'.fake()->numerify('## ### ## ##'),
            'email' => fake()->safeEmail(),
            'tc_number' => fake()->unique()->numerify('###########'),
            'courier_type' => 'independent',
            'agency_id' => null,
            'vehicle_type_id' => VehicleType::query()->where('code', 'motor')->value('id'),
            'status' => 'active',
            'start_date' => now()->subMonths(3)->toDateString(),
            'created_by' => User::factory(),
        ];
    }
}
