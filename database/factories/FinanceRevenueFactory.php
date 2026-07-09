<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceRevenue;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinanceRevenue> */
class FinanceRevenueFactory extends Factory
{
    protected $model = FinanceRevenue::class;

    public function configure(): static
    {
        return $this->afterCreating(function (FinanceRevenue $revenue): void {
            if ($revenue->reference) {
                return;
            }

            $revenue->update([
                'reference' => sprintf(
                    'GLR-%d-%06d',
                    $revenue->revenue_date->year,
                    $revenue->id,
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
            'business_id' => Business::factory(),
            'revenue_type' => 'per_package',
            'period_month' => (int) now()->format('n'),
            'period_year' => (int) now()->format('Y'),
            'period_label' => now()->translatedFormat('F Y'),
            'invoice_status' => 'none',
            'amount' => fake()->randomFloat(2, 5000, 150000),
            'vat_rate' => 20,
            'collection_status' => 'pending',
            'revenue_date' => now()->toDateString(),
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function collected(): static
    {
        return $this->state(fn () => [
            'collection_status' => 'collected',
            'collection_date' => now()->toDateString(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'collection_status' => 'overdue',
        ]);
    }

    public function withInvoice(string $invoiceNo = 'FTR-2026-0001'): static
    {
        return $this->state(fn () => [
            'invoice_no' => $invoiceNo,
            'invoice_status' => 'issued',
        ]);
    }
}
