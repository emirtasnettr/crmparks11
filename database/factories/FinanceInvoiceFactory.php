<?php

namespace Database\Factories;

use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinanceInvoice> */
class FinanceInvoiceFactory extends Factory
{
    protected $model = FinanceInvoice::class;

    public function configure(): static
    {
        return $this->afterCreating(function (FinanceInvoice $invoice): void {
            if ($invoice->reference) {
                return;
            }

            $invoice->update([
                'reference' => sprintf(
                    'FTR-%d-%06d',
                    $invoice->invoice_date->year,
                    $invoice->id,
                ),
                'pdf_filename' => $invoice->invoice_status !== 'draft'
                    ? sprintf('FTR-%d-%06d.pdf', $invoice->invoice_date->year, $invoice->id)
                    : null,
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 15000, 150000);
        $vatRate = 20;
        $vatAmount = round($subtotal * ($vatRate / 100), 2);

        return [
            'business_id' => Business::factory(),
            'invoice_type' => 'e_invoice',
            'invoice_status' => 'issued',
            'collection_status' => 'pending',
            'invoice_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'grand_total' => round($subtotal + $vatAmount, 2),
            'collected_amount' => 0,
            'source' => 'manual',
            'gib_status' => 'sent',
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function forEarning(?EarningLine $earning = null): static
    {
        return $this->state(function () use ($earning) {
            $earning ??= EarningLine::factory()->create();
            $subtotal = (float) $earning->revenue_total;
            $vatRate = 20;
            $vatAmount = round($subtotal * ($vatRate / 100), 2);

            return [
                'business_id' => $earning->business_id,
                'earning_line_id' => $earning->id,
                'source' => 'earning',
                'subtotal' => $subtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'grand_total' => round($subtotal + $vatAmount, 2),
            ];
        });
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'invoice_status' => 'draft',
            'collection_status' => 'pending',
            'collected_amount' => 0,
            'gib_status' => 'draft',
            'pdf_filename' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'invoice_status' => 'cancelled',
            'collection_status' => 'pending',
            'collected_amount' => 0,
            'gib_status' => 'cancelled',
        ]);
    }

    public function partialCollection(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_status' => 'partial',
            'collected_amount' => round(($attributes['subtotal'] ?? 10000) * 0.5, 2),
        ])->afterCreating(function (FinanceInvoice $invoice): void {
            $collection = FinanceCollection::factory()->create([
                'business_id' => $invoice->business_id,
                'current_account_id' => $invoice->current_account_id,
                'invoice_no' => $invoice->reference,
                'due_date' => $invoice->due_date,
                'total_amount' => $invoice->subtotal,
                'collected_amount' => $invoice->collected_amount,
                'status' => 'partial',
                'source' => $invoice->source,
            ]);

            $invoice->update(['collection_id' => $collection->id]);
        });
    }

    public function collected(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_status' => 'collected',
            'collected_amount' => $attributes['subtotal'] ?? 10000,
        ])->afterCreating(function (FinanceInvoice $invoice): void {
            $collection = FinanceCollection::factory()->collected()->create([
                'business_id' => $invoice->business_id,
                'current_account_id' => $invoice->current_account_id,
                'invoice_no' => $invoice->reference,
                'due_date' => $invoice->due_date,
                'total_amount' => $invoice->subtotal,
                'collected_amount' => $invoice->subtotal,
                'source' => $invoice->source,
            ]);

            $invoice->update(['collection_id' => $collection->id]);
        });
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => now()->subDays(5)->toDateString(),
            'collection_status' => 'overdue',
            'collected_amount' => 0,
        ]);
    }
}
