<?php

namespace App\Modules\Finance\Services;

use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Business\Data\BusinessEarningFormData;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use App\Modules\Setting\Services\SettingsManager;
use Illuminate\Support\Facades\DB;

class EarningFinanceSyncService
{
    public function __construct(
        private readonly RevenueService $revenues,
        private readonly PaymentService $payments,
        private readonly ExpenseService $expenses,
        private readonly SettingsManager $settings,
    ) {}

    public function syncOnApprove(EarningLine $line, User $user): void
    {
        DB::transaction(function () use ($line, $user): void {
            $line->loadMissing(['business', 'courier.agency']);

            $periodLabel = $this->periodLabel($line);
            $vatRate = (int) ($this->settings->group('finance')->all()['default_vat'] ?? 20);
            $today = now()->toDateString();
            $businessName = $line->business?->displayName() ?? 'İşletme';
            $courierName = $line->courier?->full_name ?? 'Kurye';

            $revenueAmount = round((float) $line->revenue_total + (float) $line->extra_payment, 2);

            if ($revenueAmount > 0 && ! $this->hasRevenue($line->id)) {
                $this->revenues->create([
                    'business_id' => $line->business_id,
                    'earning_line_id' => $line->id,
                    'revenue_type' => $this->mapRevenueType($line->pricing_model),
                    'period_label' => $periodLabel,
                    'revenue_date' => $today,
                    'amount' => $revenueAmount,
                    'vat_rate' => $vatRate,
                    'collection_status' => 'pending',
                    'description' => sprintf('%s — %s hakediş geliri', $businessName, $periodLabel),
                ], $user);
            }

            $courierAmount = round((float) $line->net_courier_payment, 2);

            if ($courierAmount > 0 && ! $this->hasPayment($line->id, 'courier')) {
                $this->payments->create([
                    'recipient_type' => 'courier',
                    'recipient_id' => $line->courier_id,
                    'earning_line_id' => $line->id,
                    'payment_date' => $today,
                    'total_amount' => $courierAmount,
                    'paid_amount' => 0,
                    'description' => sprintf('%s — %s kurye hakediş ödemesi', $courierName, $periodLabel),
                ], $user);
            }

            $agencyAmount = round((float) $line->agency_payment, 2);
            $agencyId = $line->courier?->agency_id;

            if ($agencyAmount > 0 && $agencyId !== null && ! $this->hasPayment($line->id, 'agency')) {
                $agencyName = $line->courier?->agency?->displayName() ?? 'Acente';

                $this->payments->create([
                    'recipient_type' => 'agency',
                    'recipient_id' => $agencyId,
                    'earning_line_id' => $line->id,
                    'payment_date' => $today,
                    'total_amount' => $agencyAmount,
                    'paid_amount' => 0,
                    'description' => sprintf('%s — %s acente hakediş ödemesi', $agencyName, $periodLabel),
                ], $user);
            }

            $extraExpense = round((float) $line->extra_expense, 2);

            if ($extraExpense > 0 && ! $this->hasExpense($line->id)) {
                $this->expenses->create([
                    'expense_type' => 'other',
                    'earning_line_id' => $line->id,
                    'expense_date' => $today,
                    'amount' => $extraExpense,
                    'vat_rate' => $vatRate,
                    'payment_status' => 'pending',
                    'description' => sprintf('%s — %s ek gider', $businessName, $periodLabel),
                ], $user);
            }
        });
    }

    public function alreadySynced(int $earningLineId): bool
    {
        return $this->hasRevenue($earningLineId)
            && $this->hasPayment($earningLineId, 'courier')
            && $this->hasExpense($earningLineId);
    }

    private function hasRevenue(int $earningLineId): bool
    {
        return FinanceRevenue::query()->where('earning_line_id', $earningLineId)->exists();
    }

    private function hasPayment(int $earningLineId, string $recipientType): bool
    {
        return FinancePayment::query()
            ->where('earning_line_id', $earningLineId)
            ->where('recipient_type', $recipientType)
            ->where('is_active', true)
            ->exists();
    }

    private function hasExpense(int $earningLineId): bool
    {
        return FinanceExpense::query()->where('earning_line_id', $earningLineId)->exists();
    }

    private function periodLabel(EarningLine $line): string
    {
        $months = BusinessEarningFormData::months();
        $month = $months[$line->period_month] ?? (string) $line->period_month;

        return trim($month.' '.$line->period_year);
    }

    private function mapRevenueType(?string $pricingModel): string
    {
        return match ($pricingModel) {
            'per_package' => 'per_package',
            'monthly_fixed' => 'fixed_monthly',
            'hourly', 'daily' => 'extra_service',
            default => 'manual',
        };
    }
}
