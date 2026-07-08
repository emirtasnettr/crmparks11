<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Agency> */
class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->company().' Acente Ltd. Şti.',
            'brand_name' => fake()->company(),
            'tax_office' => fake()->city().' Vergi Dairesi',
            'tax_number' => fake()->unique()->numerify('##########'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'city_id' => City::query()->value('id'),
            'district_id' => District::query()->value('id'),
            'address' => fake()->address(),
            'commission_rate' => 15,
            'payment_period' => 'monthly',
            'status' => 'active',
            'authorized_person' => fake()->name(),
            'created_by' => User::factory(),
        ];
    }
}
