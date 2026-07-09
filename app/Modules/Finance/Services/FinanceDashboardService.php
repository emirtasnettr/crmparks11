<?php

namespace App\Modules\Finance\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Models\EarningLine;
use App\Modules\Finance\Data\CurrentAccountFormData;
use App\Modules\Finance\Data\DashboardFormData;
use App\Modules\Finance\Data\PaymentFormData;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceInvoice;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use Carbon\Carbon;

class FinanceDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array
    {
        if (! array_key_exists($period, DashboardFormData::periods())) {
            $period = 'month';
        }

        [$rangeStart, $rangeEnd] = $this->resolveDateRange($period, $startDate, $endDate);

        return [
            'period' => $period,
            'periods' => DashboardFormData::periods(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'kpis' => $this->kpis($rangeStart, $rangeEnd),
            'charts' => $this->charts(),
            'recent_transactions' => $this->recentTransactions(),
            'pending_collections' => $this->pendingCollections(),
            'pending_payments' => $this->pendingPayments(),
            'today_summary' => $this->todaySummary(),
        ];
    }

    /**
     * @return array<string, string|float|int>
     */
    private function kpis(Carbon $start, Carbon $end): array
    {
        $revenue = (float) FinanceRevenue::query()
            ->whereBetween('revenue_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $expense = (float) FinanceExpense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $profit = round($revenue - $expense, 2);
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0.0;

        $pendingCollection = (float) FinanceCollection::query()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->selectRaw('SUM(total_amount - collected_amount) as remaining')
            ->value('remaining') ?? 0;

        $pendingPayment = (float) FinancePayment::query()
            ->where('is_active', true)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->selectRaw('SUM(total_amount - paid_amount) as remaining')
            ->value('remaining') ?? 0;

        $today = Carbon::today();

        $monthlyEarnings = EarningLine::query()
            ->whereYear('created_at', $today->year)
            ->whereMonth('created_at', $today->month)
            ->count();

        $activeAccounts = CurrentAccount::query()
            ->where('status', 'active')
            ->count();

        return [
            'total_revenue' => round($revenue, 2),
            'total_revenue_formatted' => MoneyCalculator::format($revenue),
            'total_expense' => round($expense, 2),
            'total_expense_formatted' => MoneyCalculator::format($expense),
            'net_profit' => $profit,
            'net_profit_formatted' => MoneyCalculator::format($profit),
            'profit_margin' => $margin,
            'profit_margin_formatted' => number_format($margin, 1, ',', '.').'%',
            'pending_collection' => round($pendingCollection, 2),
            'pending_collection_formatted' => MoneyCalculator::format($pendingCollection),
            'pending_payment' => round($pendingPayment, 2),
            'pending_payment_formatted' => MoneyCalculator::format($pendingPayment),
            'monthly_earnings_count' => $monthlyEarnings,
            'active_accounts' => $activeAccounts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function charts(): array
    {
        $year = (int) Carbon::today()->year;
        $monthLabels = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
        $revenue = [];
        $expense = [];

        for ($month = 1; $month <= 12; $month++) {
            $revenue[] = (int) FinanceRevenue::query()
                ->whereYear('revenue_date', $year)
                ->whereMonth('revenue_date', $month)
                ->sum('amount');

            $expense[] = (int) FinanceExpense::query()
                ->whereYear('expense_date', $year)
                ->whereMonth('expense_date', $month)
                ->sum('amount');
        }

        $profit = array_map(fn (int $r, int $e) => $r - $e, $revenue, $expense);

        $businessRevenues = FinanceRevenue::query()
            ->selectRaw('business_id, SUM(amount) as total')
            ->whereYear('revenue_date', $year)
            ->groupBy('business_id')
            ->orderByDesc('total')
            ->with('business:id,company_name,brand_name')
            ->get();

        $revenueByBusiness = $businessRevenues
            ->take(5)
            ->map(fn (FinanceRevenue $row) => [
                'label' => $row->business?->brand_name ?? $row->business?->company_name ?? '—',
                'value' => (int) round((float) $row->total),
            ])
            ->all();

        $otherRevenue = (int) round((float) $businessRevenues->skip(5)->sum('total'));

        if ($otherRevenue > 0) {
            $revenueByBusiness[] = ['label' => 'Diğer', 'value' => $otherRevenue];
        }

        $courierExpense = (int) FinanceExpense::query()
            ->whereYear('expense_date', $year)
            ->where('expense_type', 'courier_earning')
            ->sum('amount');

        $agencyExpense = (int) FinanceExpense::query()
            ->whereYear('expense_date', $year)
            ->where('expense_type', 'agency_earning')
            ->sum('amount');

        $otherExpense = (int) FinanceExpense::query()
            ->whereYear('expense_date', $year)
            ->whereNotIn('expense_type', ['courier_earning', 'agency_earning'])
            ->sum('amount');

        return [
            'months' => $monthLabels,
            'revenue_expense' => [
                'revenue' => $revenue,
                'expense' => $expense,
            ],
            'profit' => $profit,
            'revenue_by_business' => $revenueByBusiness,
            'expense_breakdown' => [
                ['label' => 'Kurye', 'value' => $courierExpense],
                ['label' => 'Acente', 'value' => $agencyExpense],
                ['label' => 'Diğer', 'value' => $otherExpense],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentTransactions(): array
    {
        return CurrentAccountMovement::query()
            ->with('currentAccount')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(15)
            ->get()
            ->map(function (CurrentAccountMovement $movement): array {
                $credit = (float) $movement->credit;
                $debit = (float) $movement->debit;
                $amount = $credit > 0 ? $credit : -$debit;
                $occurredAt = Carbon::parse($movement->transaction_date);

                return [
                    'id' => $movement->id,
                    'occurred_at' => $occurredAt->format('d.m.Y'),
                    'occurred_at_time' => $movement->created_at?->format('H:i') ?? '00:00',
                    'type' => $this->movementTypeLabel($movement->type, $movement->currentAccount),
                    'account' => $movement->currentAccount?->title ?? '—',
                    'amount' => $amount,
                    'amount_formatted' => ($amount < 0 ? '−' : '').MoneyCalculator::format(abs($amount)),
                    'is_negative' => $amount < 0,
                    'status' => 'completed',
                    'status_label' => DashboardFormData::transactionStatusLabel('completed'),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pendingCollections(): array
    {
        $today = Carbon::today();

        return FinanceCollection::query()
            ->with('business:id,company_name,brand_name')
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->limit(6)
            ->get()
            ->map(function (FinanceCollection $collection) use ($today): array {
                $due = $collection->due_date;
                $delay = (int) $today->diffInDays($due, false);
                $remaining = round((float) $collection->total_amount - (float) $collection->collected_amount, 2);

                return [
                    'business' => $collection->business?->company_name ?? '—',
                    'invoice' => $collection->invoice_no ?? $collection->reference,
                    'due_date' => $due->toDateString(),
                    'amount' => $remaining,
                    'due_date_formatted' => $due->format('d.m.Y'),
                    'amount_formatted' => MoneyCalculator::format($remaining),
                    'delay_days' => abs($delay),
                    'is_overdue' => $delay < 0,
                    'delay_label' => $delay < 0
                        ? abs($delay).' gün gecikmiş'
                        : ($delay === 0 ? 'Bugün' : $delay.' gün kaldı'),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pendingPayments(): array
    {
        return FinancePayment::query()
            ->where('is_active', true)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('scheduled_date')
            ->limit(6)
            ->get()
            ->map(function (FinancePayment $payment): array {
                $remaining = round((float) $payment->total_amount - (float) $payment->paid_amount, 2);
                $reference = $payment->earning_line_id
                    ? sprintf(
                        '%s-%d-%04d',
                        $payment->recipient_type === 'agency' ? 'AHK' : 'HKD',
                        $payment->scheduled_date->year,
                        $payment->earning_line_id,
                    )
                    : $payment->reference;

                return [
                    'payee' => $payment->recipient_name ?? '—',
                    'type' => PaymentFormData::recipientTypes()[$payment->recipient_type] ?? $payment->recipient_type,
                    'reference' => $reference,
                    'payment_date' => $payment->scheduled_date->toDateString(),
                    'amount' => $remaining,
                    'status' => $payment->status,
                    'payment_date_formatted' => $payment->scheduled_date->format('d.m.Y'),
                    'amount_formatted' => MoneyCalculator::format($remaining),
                    'status_label' => DashboardFormData::paymentStatusLabel($payment->status),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, string|int|float>
     */
    private function todaySummary(): array
    {
        $today = Carbon::today();

        $revenue = (float) FinanceRevenue::query()
            ->whereDate('revenue_date', $today)
            ->sum('amount');

        $collectedToday = (float) \App\Modules\Finance\Models\FinanceCollectionPayment::query()
            ->whereDate('payment_date', $today)
            ->sum('amount');

        $revenueTotal = $revenue + $collectedToday;

        $expense = (float) FinanceExpense::query()
            ->whereDate('expense_date', $today)
            ->sum('amount');

        $paidToday = (float) \App\Modules\Finance\Models\FinancePaymentLine::query()
            ->whereDate('payment_date', $today)
            ->sum('amount');

        $expenseTotal = $expense + $paidToday;
        $profit = round($revenueTotal - $expenseTotal, 2);

        $newEarnings = EarningLine::query()->whereDate('created_at', $today)->count();
        $newInvoices = FinanceInvoice::query()->whereDate('invoice_date', $today)->count();
        $pendingApprovals = FinancePayment::query()
            ->where('is_active', true)
            ->where('status', 'pending')
            ->count();

        return [
            'revenue' => round($revenueTotal, 2),
            'revenue_formatted' => MoneyCalculator::format($revenueTotal),
            'expense' => round($expenseTotal, 2),
            'expense_formatted' => MoneyCalculator::format($expenseTotal),
            'profit' => $profit,
            'profit_formatted' => MoneyCalculator::format($profit),
            'new_earnings' => $newEarnings,
            'new_invoices' => $newInvoices,
            'pending_approvals' => $pendingApprovals,
        ];
    }

    private function movementTypeLabel(string $type, ?CurrentAccount $account): string
    {
        if ($type === 'payment') {
            return match ($account?->account_type) {
                'courier' => 'Kurye Ödemesi',
                'agency' => 'Acente Hakediş',
                default => 'Ödeme',
            };
        }

        return match ($type) {
            'collection' => 'Tahsilat',
            'invoice' => 'Fatura Kesimi',
            'earning' => 'Gelir',
            'debit_note' => 'Gider',
            'credit_note' => 'Alacak Dekontu',
            default => CurrentAccountFormData::movementTypeLabels()[$type] ?? 'Hareket',
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(string $period, ?string $startDate, ?string $endDate): array
    {
        $today = Carbon::today();

        return match ($period) {
            'today' => [$today->copy(), $today->copy()],
            'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'custom' => [
                Carbon::parse($startDate ?: $today->toDateString()),
                Carbon::parse($endDate ?: $today->toDateString()),
            ],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };
    }
}
