<?php

namespace Database\Factories;

use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BusinessContact> */
class BusinessContactFactory extends Factory
{
    protected $model = BusinessContact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'full_name' => fake()->name(),
            'title' => fake()->randomElement([
                'İşletme Sahibi',
                'Şube Müdürü',
                'Operasyon Müdürü',
                'Restoran Müdürü',
                'Muhasebe Yetkilisi',
            ]),
            'phone' => '05'.fake()->numerify('## ### ## ##'),
            'email' => fake()->safeEmail(),
            'is_default' => false,
            'status' => 'active',
        ];
    }
}
