<?php

namespace Database\Factories;

use App\Modules\Finance\Models\CurrentAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CurrentAccount> */
class CurrentAccountFactory extends Factory
{
    protected $model = CurrentAccount::class;

    public function configure(): static
    {
        return $this->afterCreating(function (CurrentAccount $account): void {
            if ($account->code) {
                return;
            }

            $account->update([
                'code' => sprintf('CAR-%06d', $account->id),
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_type' => 'business',
            'title' => fake()->company(),
            'phone' => fake()->numerify('0212 ### ## ##'),
            'email' => fake()->safeEmail(),
            'tax_number' => fake()->numerify('##########'),
            'city' => fake()->city(),
            'status' => 'active',
        ];
    }

    public function business(): static
    {
        return $this->state(fn () => ['account_type' => 'business']);
    }

    public function courier(): static
    {
        return $this->state(fn () => ['account_type' => 'courier']);
    }

    public function agency(): static
    {
        return $this->state(fn () => ['account_type' => 'agency']);
    }

    public function passive(): static
    {
        return $this->state(fn () => ['status' => 'passive']);
    }
}
