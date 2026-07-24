<?php

namespace App\Modules\Finance\Services;

use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Business\Data\BusinessEarningFormData;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use App\Modules\Setting\Services\SettingsManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EarningFinanceSyncService
{
    public function __construct(
        private readonly RevenueService $revenues,
        private readonly PaymentService $payments,
        private readonly ExpenseService $expenses,
        private readonly CurrentAccountService $currentAccounts,
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

    /**
     * Hakediş silinince bağlı finans kayıtlarını ve cari hareketlerini geri alır.
     *
     * @throws ValidationException
     */
    public function syncOnDelete(EarningLine $line, User $user): void
    {
        DB::transaction(function () use ($line, $user): void {
            $payments = FinancePayment::query()
                ->where('earning_line_id', $line->id)
                ->where('is_active', true)
                ->get();

            foreach ($payments as $payment) {
                if (round((float) $payment->paid_amount, 2) > 0) {
                    throw ValidationException::withMessages([
                        'earning' => 'Ödemesi başlamış hakediş silinemez. Önce finans ödemesini iptal edin.',
                    ]);
                }

                $this->reversePaymentLiability($payment, $user);
                $payment->update([
                    'is_active' => false,
                    'status' => 'cancelled',
                    'earning_line_id' => null,
                ]);
            }

            $revenues = FinanceRevenue::query()
                ->where('earning_line_id', $line->id)
                ->where('collection_status', '!=', 'cancelled')
                ->get();

            foreach ($revenues as $revenue) {
                if (in_array($revenue->collection_status, ['collected', 'partial'], true)) {
                    throw ValidationException::withMessages([
                        'earning' => 'Tahsilatı başlamış hakediş geliri silinemez.',
                    ]);
                }

                $this->reverseRevenueReceivable($revenue, $user);
                $revenue->update([
                    'collection_status' => 'cancelled',
                    'earning_line_id' => null,
                    'description' => trim(($revenue->description ?? '').' [hakediş silindi]'),
                ]);
            }

            FinanceExpense::query()
                ->where('earning_line_id', $line->id)
                ->where('payment_status', '!=', 'cancelled')
                ->get()
                ->each(function (FinanceExpense $expense): void {
                    if (in_array($expense->payment_status, ['paid', 'partial'], true)) {
                        throw ValidationException::withMessages([
                            'earning' => 'Ödenmiş ek gideri olan hakediş silinemez.',
                        ]);
                    }

                    $expense->update([
                        'payment_status' => 'cancelled',
                        'earning_line_id' => null,
                    ]);
                });
        });
    }

    /**
     * Soft-delete edilmiş veya kopuk hakediş finans kayıtlarını temizler.
     *
     * @return array{payments: int, revenues: int, expenses: int}
     */
    public function cleanupOrphanFinance(User $user): array
    {
        $payments = 0;
        $revenues = 0;
        $expenses = 0;

        EarningLine::onlyTrashed()
            ->orderBy('id')
            ->each(function (EarningLine $line) use ($user, &$payments, &$revenues, &$expenses): void {
                $beforePay = FinancePayment::query()
                    ->where('earning_line_id', $line->id)
                    ->where('is_active', true)
                    ->count();
                $beforeRev = FinanceRevenue::query()
                    ->where('earning_line_id', $line->id)
                    ->where('collection_status', '!=', 'cancelled')
                    ->count();
                $beforeExp = FinanceExpense::query()
                    ->where('earning_line_id', $line->id)
                    ->where('payment_status', '!=', 'cancelled')
                    ->count();

                try {
                    $this->syncOnDelete($line, $user);
                    $payments += $beforePay;
                    $revenues += $beforeRev;
                    $expenses += $beforeExp;
                } catch (\Throwable $e) {
                    report($e);
                }
            });

        FinancePayment::query()
            ->whereNotNull('earning_line_id')
            ->where('is_active', true)
            ->whereNotIn('earning_line_id', EarningLine::withTrashed()->pluck('id'))
            ->each(function (FinancePayment $payment) use ($user, &$payments): void {
                if (round((float) $payment->paid_amount, 2) > 0) {
                    return;
                }

                $this->reversePaymentLiability($payment, $user);
                $payment->update(['is_active' => false, 'status' => 'cancelled', 'earning_line_id' => null]);
                $payments++;
            });

        FinanceRevenue::query()
            ->whereNotNull('earning_line_id')
            ->where('collection_status', '!=', 'cancelled')
            ->whereNotIn('earning_line_id', EarningLine::withTrashed()->pluck('id'))
            ->each(function (FinanceRevenue $revenue) use ($user, &$revenues): void {
                if (in_array($revenue->collection_status, ['collected', 'partial'], true)) {
                    return;
                }

                $this->reverseRevenueReceivable($revenue, $user);
                $revenue->update([
                    'collection_status' => 'cancelled',
                    'earning_line_id' => null,
                ]);
                $revenues++;
            });

        FinanceExpense::query()
            ->whereNotNull('earning_line_id')
            ->where('payment_status', '!=', 'cancelled')
            ->whereNotIn('earning_line_id', EarningLine::withTrashed()->pluck('id'))
            ->each(function (FinanceExpense $expense) use (&$expenses): void {
                if (in_array($expense->payment_status, ['paid', 'partial'], true)) {
                    return;
                }

                $expense->update([
                    'payment_status' => 'cancelled',
                    'earning_line_id' => null,
                ]);
                $expenses++;
            });

        return compact('payments', 'revenues', 'expenses');
    }

    public function alreadySynced(int $earningLineId): bool
    {
        $line = EarningLine::query()->with('courier')->find($earningLineId);
        if ($line === null) {
            return false;
        }

        $needsCourier = round((float) $line->net_courier_payment, 2) > 0;
        $needsAgency = round((float) $line->agency_payment, 2) > 0
            && $line->courier?->agency_id !== null;
        $needsExpense = round((float) $line->extra_expense, 2) > 0;
        $needsRevenue = round((float) $line->revenue_total + (float) $line->extra_payment, 2) > 0;

        return (! $needsRevenue || $this->hasRevenue($earningLineId))
            && (! $needsCourier || $this->hasPayment($earningLineId, 'courier'))
            && (! $needsAgency || $this->hasPayment($earningLineId, 'agency'))
            && (! $needsExpense || $this->hasExpense($earningLineId));
    }

    private function reversePaymentLiability(FinancePayment $payment, User $user): void
    {
        if ($payment->current_account_id === null) {
            return;
        }

        $liability = CurrentAccountMovement::query()
            ->where('current_account_id', $payment->current_account_id)
            ->where('type', 'earning')
            ->where('related_type', FinancePayment::class)
            ->where('related_id', $payment->id)
            ->first();

        if ($liability === null) {
            return;
        }

        $alreadyReversed = CurrentAccountMovement::query()
            ->where('current_account_id', $payment->current_account_id)
            ->where('type', 'debit_note')
            ->where('related_type', FinancePayment::class)
            ->where('related_id', $payment->id)
            ->where('description', 'like', 'Hakediş iptali:%')
            ->exists();

        if ($alreadyReversed) {
            return;
        }

        $this->currentAccounts->createMovement([
            'current_account_id' => (int) $payment->current_account_id,
            'transaction_date' => now()->toDateString(),
            'type' => 'debit_note',
            'document_no' => $payment->reference,
            'amount' => (float) $payment->total_amount,
            'description' => 'Hakediş iptali: '.$payment->reference,
            'related_type' => FinancePayment::class,
            'related_id' => $payment->id,
        ], $user);
    }

    private function reverseRevenueReceivable(FinanceRevenue $revenue, User $user): void
    {
        if ($revenue->current_account_id === null) {
            return;
        }

        $receivable = CurrentAccountMovement::query()
            ->where('current_account_id', $revenue->current_account_id)
            ->where('related_type', FinanceRevenue::class)
            ->where('related_id', $revenue->id)
            ->where('debit', '>', 0)
            ->first();

        if ($receivable === null) {
            return;
        }

        $alreadyReversed = CurrentAccountMovement::query()
            ->where('current_account_id', $revenue->current_account_id)
            ->where('type', 'credit_note')
            ->where('related_type', FinanceRevenue::class)
            ->where('related_id', $revenue->id)
            ->where('description', 'like', 'Hakediş iptali:%')
            ->exists();

        if ($alreadyReversed) {
            return;
        }

        $this->currentAccounts->createMovement([
            'current_account_id' => (int) $revenue->current_account_id,
            'transaction_date' => now()->toDateString(),
            'type' => 'credit_note',
            'document_no' => $revenue->reference,
            'amount' => (float) $revenue->amount,
            'description' => 'Hakediş iptali: '.$revenue->reference,
            'related_type' => FinanceRevenue::class,
            'related_id' => $revenue->id,
        ], $user);
    }

    private function hasRevenue(int $earningLineId): bool
    {
        return FinanceRevenue::query()
            ->where('earning_line_id', $earningLineId)
            ->where('collection_status', '!=', 'cancelled')
            ->exists();
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
        return FinanceExpense::query()
            ->where('earning_line_id', $earningLineId)
            ->where('payment_status', '!=', 'cancelled')
            ->exists();
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
