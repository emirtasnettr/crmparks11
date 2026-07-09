<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinancePaymentLine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinancePayment> */
class FinancePaymentFactory extends Factory
{
    protected $model = FinancePayment::class;

    public function configure(): static
    {
        return $this->afterCreating(function (FinancePayment $payment): void {
            if ($payment->reference) {
                return;
            }

            $payment->update([
                'reference' => sprintf(
                    'ODM-%d-%06d',
                    $payment->scheduled_date->year,
                    $payment->id,
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
            'recipient_type' => 'courier',
            'courier_id' => Courier::factory(),
            'source' => 'manual',
            'scheduled_date' => now()->addDays(5)->toDateString(),
            'total_amount' => fake()->randomFloat(2, 8500, 120000),
            'paid_amount' => 0,
            'status' => 'pending',
            'is_active' => true,
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function forCourier(?Courier $courier = null): static
    {
        return $this->state(function () use ($courier) {
            $courier ??= Courier::factory()->create();

            return [
                'recipient_type' => 'courier',
                'courier_id' => $courier->id,
                'agency_id' => null,
                'recipient_id' => $courier->id,
                'recipient_name' => $courier->full_name,
            ];
        });
    }

    public function forAgency(?Agency $agency = null): static
    {
        return $this->state(function () use ($agency) {
            $agency ??= Agency::factory()->create();

            return [
                'recipient_type' => 'agency',
                'agency_id' => $agency->id,
                'courier_id' => null,
                'recipient_id' => $agency->id,
                'recipient_name' => $agency->company_name,
            ];
        });
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_amount' => $attributes['total_amount'] ?? 10000,
            'status' => 'paid',
        ])->afterCreating(function (FinancePayment $payment): void {
            FinancePaymentLine::query()->create([
                'payment_id' => $payment->id,
                'amount' => $payment->total_amount,
                'payment_date' => $payment->scheduled_date->copy()->subDays(2)->toDateString(),
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'PAY-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT),
                'bank_account' => 'Garanti BBVA — TR1000000000000001',
                'created_by' => $payment->created_by,
            ]);
        });
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_amount' => round(($attributes['total_amount'] ?? 10000) * 0.55, 2),
            'status' => 'partial',
        ])->afterCreating(function (FinancePayment $payment): void {
            $total = (float) $payment->total_amount;
            $first = round($total * 0.35, 2);
            $second = round($payment->paid_amount - $first, 2);

            FinancePaymentLine::query()->create([
                'payment_id' => $payment->id,
                'amount' => $first,
                'payment_date' => Carbon::parse($payment->scheduled_date)->subDays(10)->toDateString(),
                'payment_method' => 'eft',
                'payment_reference' => 'PAY-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT).'-1',
                'bank_account' => 'İş Bankası — TR1000000000000002',
                'created_by' => $payment->created_by,
            ]);

            FinancePaymentLine::query()->create([
                'payment_id' => $payment->id,
                'amount' => $second,
                'payment_date' => Carbon::parse($payment->scheduled_date)->subDays(2)->toDateString(),
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'PAY-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT).'-2',
                'bank_account' => 'Garanti BBVA — TR1000000000000001',
                'created_by' => $payment->created_by,
            ]);
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'paid_amount' => 0,
            'status' => 'cancelled',
            'is_active' => false,
        ]);
    }
}
