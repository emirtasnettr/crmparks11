<?php

namespace Database\Factories;

use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgencyContact> */
class AgencyContactFactory extends Factory
{
    protected $model = AgencyContact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'full_name' => fake()->name(),
            'title' => fake()->randomElement([
                'Firma Sahibi',
                'Operasyon Müdürü',
                'Finans Sorumlusu',
                'İnsan Kaynakları',
                'Muhasebe Yetkilisi',
            ]),
            'phone' => '05'.fake()->numerify('## ### ## ##'),
            'email' => fake()->safeEmail(),
            'is_default' => false,
            'status' => 'active',
        ];
    }
}
