<?php

namespace Database\Factories;

use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CourierBankAccount> */
class CourierBankAccountFactory extends Factory
{
    protected $model = CourierBankAccount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'courier_id' => Courier::factory(),
            'bank_key' => fake()->randomElement(['ziraat', 'isbank', 'garanti', 'akbank']),
            'account_holder' => fake()->name(),
            'iban' => 'TR'.fake()->numerify('####################'),
            'branch_code' => fake()->numerify('####'),
            'account_number' => fake()->numerify('##########'),
            'is_default' => true,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
