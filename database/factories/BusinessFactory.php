<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\District;
use App\Models\PricingModelType;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessPricing;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Business> */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Business $business): void {
            if ($business->activePricing()->exists()) {
                return;
            }

            $pricingModel = PricingModelType::query()
                ->where('code', 'per_package')
                ->first();

            if ($pricingModel === null) {
                return;
            }

            BusinessPricing::query()->create([
                'business_id' => $business->id,
                'pricing_model_type_id' => $pricingModel->id,
                'customer_unit_price' => 45,
                'courier_unit_price' => 32,
                'effective_from' => now()->toDateString(),
                'is_active' => true,
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
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
