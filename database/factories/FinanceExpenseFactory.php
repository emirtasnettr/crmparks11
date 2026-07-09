<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinanceExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinanceExpense> */
class FinanceExpenseFactory extends Factory
{
    protected $model = FinanceExpense::class;

    public function configure(): static
    {
        return $this->afterCreating(function (FinanceExpense $expense): void {
            if ($expense->reference) {
                return;
            }

            $expense->update([
                'reference' => sprintf(
                    'GDR-%d-%06d',
                    $expense->expense_date->year,
                    $expense->id,
                ),
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_type' => 'personnel',
            'source' => 'manual',
            'amount' => fake()->randomFloat(2, 3000, 80000),
            'vat_rate' => 20,
            'expense_date' => now()->toDateString(),
            'payment_status' => 'pending',
            'document_no' => sprintf('BLG-%d-%04d', now()->year, fake()->numberBetween(1, 9999)),
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function courierEarning(): static
    {
        return $this->state(fn () => [
            'expense_type' => 'courier_earning',
            'source' => 'earning',
            'courier_id' => Courier::factory(),
        ]);
    }

    public function agencyEarning(): static
    {
        return $this->state(fn () => [
            'expense_type' => 'agency_earning',
            'source' => 'earning',
            'agency_id' => Agency::factory(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'payment_status' => 'paid',
            'payment_date' => now()->toDateString(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'payment_status' => 'overdue',
        ]);
    }
}
