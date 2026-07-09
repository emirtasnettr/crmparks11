<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceCollectionPayment;
use App\Modules\Finance\Models\FinanceRevenue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinanceCollection> */
class FinanceCollectionFactory extends Factory
{
    protected $model = FinanceCollection::class;

    public function configure(): static
    {
        return $this->afterCreating(function (FinanceCollection $collection): void {
            if ($collection->reference) {
                return;
            }

            $collection->update([
                'reference' => sprintf(
                    'TAH-%d-%06d',
                    $collection->due_date->year,
                    $collection->id,
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
            'source' => 'manual',
            'due_date' => now()->addDays(10)->toDateString(),
            'total_amount' => fake()->randomFloat(2, 12000, 120000),
            'collected_amount' => 0,
            'status' => 'pending',
            'invoice_no' => sprintf('FTR-%d-%04d', now()->year, fake()->numberBetween(1, 9999)),
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function forRevenue(?FinanceRevenue $revenue = null): static
    {
        return $this->state(function () use ($revenue) {
            $revenue ??= FinanceRevenue::factory()->create();

            return [
                'business_id' => $revenue->business_id,
                'revenue_id' => $revenue->id,
                'current_account_id' => $revenue->current_account_id,
                'source' => 'revenue',
                'invoice_no' => $revenue->invoice_no,
                'total_amount' => $revenue->amount,
            ];
        });
    }

    public function collected(): static
    {
        return $this->state(fn (array $attributes) => [
            'collected_amount' => $attributes['total_amount'] ?? 10000,
            'status' => 'collected',
        ])->afterCreating(function (FinanceCollection $collection): void {
            FinanceCollectionPayment::query()->create([
                'collection_id' => $collection->id,
                'amount' => $collection->total_amount,
                'payment_date' => $collection->due_date->copy()->subDays(2)->toDateString(),
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'REF-'.str_pad((string) $collection->id, 5, '0', STR_PAD_LEFT),
                'bank' => 'Garanti BBVA',
                'created_by' => $collection->created_by,
            ]);
        });
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'collected_amount' => round(($attributes['total_amount'] ?? 10000) * 0.6, 2),
            'status' => 'partial',
        ])->afterCreating(function (FinanceCollection $collection): void {
            $total = (float) $collection->total_amount;
            $first = round($total * 0.35, 2);
            $second = round($collection->collected_amount - $first, 2);

            FinanceCollectionPayment::query()->create([
                'collection_id' => $collection->id,
                'amount' => $first,
                'payment_date' => Carbon::parse($collection->due_date)->subDays(7)->toDateString(),
                'payment_method' => 'eft',
                'payment_reference' => 'REF-'.str_pad((string) $collection->id, 5, '0', STR_PAD_LEFT).'-1',
                'bank' => 'İş Bankası',
                'created_by' => $collection->created_by,
            ]);

            FinanceCollectionPayment::query()->create([
                'collection_id' => $collection->id,
                'amount' => $second,
                'payment_date' => Carbon::parse($collection->due_date)->subDays(2)->toDateString(),
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'REF-'.str_pad((string) $collection->id, 5, '0', STR_PAD_LEFT).'-2',
                'bank' => 'Garanti BBVA',
                'created_by' => $collection->created_by,
            ]);
        });
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => now()->subDays(10)->toDateString(),
            'collected_amount' => 0,
            'status' => 'overdue',
        ]);
    }
}
