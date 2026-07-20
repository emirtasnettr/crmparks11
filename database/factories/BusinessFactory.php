<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCommercialContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Business> */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Business $business): void {
            if ($business->activeCommercialContract()->exists()) {
                return;
            }

            BusinessCommercialContract::factory()->perPackage()->create([
                'business_id' => $business->id,
                'business_amount' => 45,
                'courier_amount' => 32,
                'net_profit' => 13,
                'created_by' => $business->created_by,
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->company().' Ltd. Şti.',
            'brand_name' => fake()->company(),
            'tax_office' => fake()->city().' Vergi Dairesi',
            'tax_number' => fake()->unique()->numerify('##########'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->url(),
            'city_id' => City::query()->value('id'),
            'district_id' => District::query()->value('id'),
            'address' => fake()->address(),
            'status' => 'active',
            'earning_period' => 'weekly',
            'first_invoice_date' => \App\Modules\Business\Data\BusinessFormData::defaultFirstInvoiceDate(),
            'planned_courier_count' => fake()->numberBetween(2, 12),
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
