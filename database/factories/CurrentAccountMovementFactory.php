<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CurrentAccountMovement> */
class CurrentAccountMovementFactory extends Factory
{
    protected $model = CurrentAccountMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 1000, 50000);

        return [
            'current_account_id' => CurrentAccount::factory(),
            'transaction_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'document_no' => sprintf('MHS-%s-%04d', now()->format('Y'), fake()->numberBetween(1, 9999)),
            'type' => 'collection',
            'debit' => 0,
            'credit' => $amount,
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function debit(float $amount): static
    {
        return $this->state(fn () => [
            'type' => 'payment',
            'debit' => $amount,
            'credit' => 0,
        ]);
    }

    public function credit(float $amount): static
    {
        return $this->state(fn () => [
            'type' => 'collection',
            'debit' => 0,
            'credit' => $amount,
        ]);
    }
}
