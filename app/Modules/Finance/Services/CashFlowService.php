<?php

namespace App\Modules\Finance\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Modules\Finance\Data\CashFlowFormData;
use App\Modules\Finance\Models\FinanceCollectionPayment;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinancePaymentLine;
use App\Modules\Finance\Models\FinanceRevenue;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CashFlowService
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $recordsCache = null;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function analyze(array $filters): array
    {
        $period = $filters['period'] ?? 'month';
        if (! array_key_exists($period, CashFlowFormData::periods())) {
            $period = 'month';
        }

        [$start, $end] = $this->resolvePeriodRange($period, $filters['start_date'] ?? null, $filters['end_date'] ?? null);

        $allWithBalance = $this->recordsWithBalance();
        $filtered = $this->filterByPeriod($allWithBalance, $start, $end);

        $previousStart = $start->copy()->subDays($start->diffInDays($end) + 1);
        $previousEnd = $start->copy()->subDay();
        $previousFiltered = $this->filterByPeriod($allWithBalance, $previousStart, $previousEnd);

        $kpis = $this->buildKpis($filtered, $previousFiltered);
        $charts = $this->buildCharts($filtered, $start, $end);
        $sidebar = $this->buildSidebar($allWithBalance);

        $total = count($filtered);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = 25;
        $items = collect($filtered)
            ->sortByDesc('occurred_at')
            ->values()
            ->slice(($page - 1) * $perPage, $perPage)
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'kpis' => $kpis,
            'charts' => $charts,
            'sidebar' => $sidebar,
            'transactions' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recordsWithBalance(): array
    {
        if ($this->recordsCache !== null) {
            return $this->recordsCache;
        }

        $records = [];
        $sequence = 0;

        FinanceCollectionPayment::query()
            ->with(['collection.business', 'creator'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->each(function (FinanceCollectionPayment $payment) use (&$records, &$sequence): void {
                $collection = $payment->collection;
                $amount = (float) $payment->amount;
                $occurredAt = Carbon::parse($payment->payment_date)
                    ->setTimeFromTimeString($payment->created_at?->format('H:i:s') ?? '12:00:00');

                $records[] = [
                    'id' => ++$sequence,
                    'reference' => sprintf('NKT-%d-%06d', $occurredAt->year, $sequence),
                    'occurred_at' => $occurredAt->toDateTimeString(),
                    'transaction_type' => 'collection',
                    'source_type' => 'collection',
                    'source_module' => 'collections',
                    'source_id' => $payment->collection_id,
                    'document_reference' => $collection?->reference ?? $collection?->invoice_no ?? '—',
                    'current_account_id' => $collection?->business_id,
                    'current_account_name' => $collection?->business?->displayName() ?? '—',
                    'description' => 'İşletme tahsilatı — '.($collection?->business?->displayName() ?? '—'),
                    'amount_in' => $amount,
                    'amount_out' => 0.0,
                    'performed_by' => $payment->creator?->name ?? 'Sistem',
                ];
            });

        FinancePaymentLine::query()
            ->with(['payment', 'creator'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->each(function (FinancePaymentLine $line) use (&$records, &$sequence): void {
                $payment = $line->payment;
                $amount = (float) $line->amount;
                $occurredAt = Carbon::parse($line->payment_date)
                    ->setTimeFromTimeString($line->created_at?->format('H:i:s') ?? '12:00:00');
                $sourceType = $payment?->earning_line_id ? 'earning' : 'payment';

                $records[] = [
                    'id' => ++$sequence,
                    'reference' => sprintf('NKT-%d-%06d', $occurredAt->year, $sequence),
                    'occurred_at' => $occurredAt->toDateTimeString(),
                    'transaction_type' => 'payment',
                    'source_type' => $sourceType,
                    'source_module' => 'payments',
                    'source_id' => $line->payment_id,
                    'document_reference' => $payment?->reference ?? '—',
                    'current_account_id' => $payment?->recipient_id,
                    'current_account_name' => $payment?->recipient_name ?? '—',
                    'description' => 'Kurye/acente ödemesi — '.($payment?->recipient_name ?? '—'),
                    'amount_in' => 0.0,
                    'amount_out' => $amount,
                    'performed_by' => $line->creator?->name ?? 'Sistem',
                ];
            });

        FinanceRevenue::query()
            ->with(['business', 'creator'])
            ->orderBy('revenue_date')
            ->orderBy('id')
            ->get()
            ->each(function (FinanceRevenue $revenue) use (&$records, &$sequence): void {
                $amount = (float) $revenue->amount;
                $occurredAt = Carbon::parse($revenue->revenue_date)
                    ->setTimeFromTimeString($revenue->created_at?->format('H:i:s') ?? '09:00:00');

                $records[] = [
                    'id' => ++$sequence,
                    'reference' => sprintf('NKT-%d-%06d', $occurredAt->year, $sequence),
                    'occurred_at' => $occurredAt->toDateTimeString(),
                    'transaction_type' => 'revenue',
                    'source_type' => 'revenue',
                    'source_module' => 'revenues',
                    'source_id' => $revenue->id,
                    'document_reference' => $revenue->reference,
                    'current_account_id' => $revenue->business_id,
                    'current_account_name' => $revenue->business?->displayName() ?? '—',
                    'description' => 'Gelir kaydı tahakkuku — '.($revenue->business?->displayName() ?? '—'),
                    'amount_in' => $amount,
                    'amount_out' => 0.0,
                    'performed_by' => $revenue->creator?->name ?? 'Sistem',
                ];
            });

        FinanceExpense::query()
            ->with(['courier', 'agency', 'creator'])
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get()
            ->each(function (FinanceExpense $expense) use (&$records, &$sequence): void {
                $amount = (float) $expense->amount;
                $occurredAt = Carbon::parse($expense->expense_date)
                    ->setTimeFromTimeString($expense->created_at?->format('H:i:s') ?? '09:00:00');
                $cari = $expense->courier?->full_name
                    ?? $expense->agency?->company_name
                    ?? '—';

                $records[] = [
                    'id' => ++$sequence,
                    'reference' => sprintf('NKT-%d-%06d', $occurredAt->year, $sequence),
                    'occurred_at' => $occurredAt->toDateTimeString(),
                    'transaction_type' => 'expense',
                    'source_type' => 'expense',
                    'source_module' => 'expenses',
                    'source_id' => $expense->id,
                    'document_reference' => $expense->reference,
                    'current_account_id' => $expense->courier_id ?? $expense->agency_id,
                    'current_account_name' => $cari,
                    'description' => 'Operasyonel gider — '.$cari,
                    'amount_in' => 0.0,
                    'amount_out' => $amount,
                    'performed_by' => $expense->creator?->name ?? 'Sistem',
                ];
            });

        usort($records, fn (array $a, array $b) => strcmp($a['occurred_at'], $b['occurred_at']));

        $balance = 0.0;
        foreach ($records as &$record) {
            $balance = round($balance + $record['amount_in'] - $record['amount_out'], 2);
            $record['balance'] = $balance;
        }
        unset($record);

        $this->recordsCache = array_map(fn (array $row) => $this->enrich($row), $records);

        return $this->recordsCache;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function enrich(array $row): array
    {
        $occurred = Carbon::parse($row['occurred_at']);

        return array_merge($row, [
            'transaction_type_label' => CashFlowFormData::transactionTypes()[$row['transaction_type']] ?? $row['transaction_type'],
            'source_type_label' => CashFlowFormData::sourceTypes()[$row['source_type']] ?? $row['source_type'],
            'date_formatted' => $occurred->format('d.m.Y'),
            'time_formatted' => $occurred->format('H:i'),
            'amount_in_formatted' => $row['amount_in'] > 0 ? MoneyCalculator::format($row['amount_in']) : '—',
            'amount_out_formatted' => $row['amount_out'] > 0 ? MoneyCalculator::format($row['amount_out']) : '—',
            'balance_formatted' => MoneyCalculator::format($row['balance']),
            'related_url' => $this->resolveRelatedUrl($row),
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveRelatedUrl(array $row): ?string
    {
        $sourceId = $row['source_id'] ?? null;

        if ($sourceId === null) {
            return null;
        }

        return match ($row['source_module'] ?? null) {
            'collections' => route('finance.collections.show', $sourceId),
            'payments' => route('finance.payments.show', $sourceId),
            'revenues' => route('finance.revenues.show', $sourceId),
            'expenses' => route('finance.expenses.show', $sourceId),
            default => null,
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriodRange(string $period, ?string $startDate, ?string $endDate): array
    {
        $today = Carbon::today();

        if ($period === 'custom' && $startDate && $endDate) {
            return [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ];
        }

        return match ($period) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    private function filterByPeriod(array $records, Carbon $start, Carbon $end): array
    {
        return collect($records)
            ->filter(fn (array $row) => Carbon::parse($row['occurred_at'])->between($start, $end))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $previousItems
     * @return array<string, mixed>
     */
    private function buildKpis(array $items, array $previousItems): array
    {
        $cashIn = round(collect($items)->sum('amount_in'), 2);
        $cashOut = round(collect($items)->sum('amount_out'), 2);
        $netCash = round($cashIn - $cashOut, 2);

        $prevNet = round(collect($previousItems)->sum('amount_in') - collect($previousItems)->sum('amount_out'), 2);
        $changeRate = $prevNet != 0.0
            ? round((($netCash - $prevNet) / abs($prevNet)) * 100, 1)
            : ($netCash > 0 ? 100.0 : 0.0);

        $pendingCollection = (float) \App\Modules\Finance\Models\FinanceCollection::query()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->selectRaw('SUM(total_amount - collected_amount) as remaining')
            ->value('remaining') ?? 0;

        $pendingPayment = (float) \App\Modules\Finance\Models\FinancePayment::query()
            ->where('is_active', true)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->selectRaw('SUM(total_amount - paid_amount) as remaining')
            ->value('remaining') ?? 0;

        return [
            'cash_in' => $cashIn,
            'cash_in_formatted' => MoneyCalculator::format($cashIn),
            'cash_out' => $cashOut,
            'cash_out_formatted' => MoneyCalculator::format($cashOut),
            'net_cash' => $netCash,
            'net_cash_formatted' => MoneyCalculator::format($netCash),
            'pending_collections' => round($pendingCollection, 2),
            'pending_collections_formatted' => MoneyCalculator::format($pendingCollection),
            'pending_payments' => round($pendingPayment, 2),
            'pending_payments_formatted' => MoneyCalculator::format($pendingPayment),
            'cash_change_rate' => $changeRate,
            'cash_change_rate_formatted' => ($changeRate >= 0 ? '+' : '').number_format($changeRate, 1, ',', '.').'%',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function buildCharts(array $items, Carbon $start, Carbon $end): array
    {
        $daily = collect($items)
            ->groupBy(fn (array $row) => Carbon::parse($row['occurred_at'])->format('Y-m-d'))
            ->sortKeys();

        $labels = [];
        $balanceSeries = [];
        $dailyIn = [];
        $dailyOut = [];
        $cursor = $start->copy()->startOfDay();
        $running = $this->openingBalanceBefore($start);

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $dayRows = $daily->get($key, collect());
            $in = round($dayRows->sum('amount_in'), 2);
            $out = round($dayRows->sum('amount_out'), 2);
            $running = round($running + $in - $out, 2);

            $labels[] = $cursor->format('d.m');
            $balanceSeries[] = (int) round($running);
            $dailyIn[] = (int) round($in);
            $dailyOut[] = (int) round($out);

            $cursor->addDay();
        }

        $inflowBySource = collect($items)
            ->where('amount_in', '>', 0)
            ->groupBy('source_type')
            ->map(fn (Collection $group, string $source) => [
                'label' => CashFlowFormData::sourceTypes()[$source] ?? $source,
                'value' => (int) round($group->sum('amount_in')),
            ])
            ->values()
            ->all();

        $outflowBySource = collect($items)
            ->where('amount_out', '>', 0)
            ->groupBy('source_type')
            ->map(fn (Collection $group, string $source) => [
                'label' => CashFlowFormData::sourceTypes()[$source] ?? $source,
                'value' => (int) round($group->sum('amount_out')),
            ])
            ->values()
            ->all();

        $pendingCollection = (float) \App\Modules\Finance\Models\FinanceCollection::query()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->selectRaw('SUM(total_amount - collected_amount) as remaining')
            ->value('remaining') ?? 0;

        $pendingPayment = (float) \App\Modules\Finance\Models\FinancePayment::query()
            ->where('is_active', true)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->selectRaw('SUM(total_amount - paid_amount) as remaining')
            ->value('remaining') ?? 0;

        return [
            'labels' => $labels,
            'cash_flow' => [
                'balance' => $balanceSeries,
            ],
            'daily_movement' => [
                'in' => $dailyIn,
                'out' => $dailyOut,
            ],
            'distribution' => [
                ['label' => 'Gelir / Tahsilat', 'value' => (int) round(collect($items)->sum('amount_in'))],
                ['label' => 'Gider / Ödeme', 'value' => (int) round(collect($items)->sum('amount_out'))],
            ],
            'inflow_by_source' => $inflowBySource,
            'outflow_by_source' => $outflowBySource,
            'pending_comparison' => [
                'collections' => (int) round($pendingCollection),
                'payments' => (int) round($pendingPayment),
            ],
        ];
    }

    private function openingBalanceBefore(Carbon $date): float
    {
        $before = collect($this->recordsWithBalance())
            ->filter(fn (array $row) => Carbon::parse($row['occurred_at'])->lt($date->copy()->startOfDay()));

        if ($before->isEmpty()) {
            return 0.0;
        }

        return (float) $before->last()['balance'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $all
     * @return array<string, mixed>
     */
    private function buildSidebar(array $all): array
    {
        $today = Carbon::today();
        $todayRows = collect($all)->filter(
            fn (array $row) => Carbon::parse($row['occurred_at'])->isSameDay($today)
        );

        $collections = $todayRows->where('transaction_type', 'collection');
        $payments = $todayRows->where('transaction_type', 'payment');

        $largestCollection = $collections->sortByDesc('amount_in')->first();
        $largestPayment = $payments->sortByDesc('amount_out')->first();

        return [
            'today_movements' => $todayRows->count(),
            'today_collections_count' => $collections->count(),
            'today_collections_total_formatted' => MoneyCalculator::format($collections->sum('amount_in')),
            'today_payments_count' => $payments->count(),
            'today_payments_total_formatted' => MoneyCalculator::format($payments->sum('amount_out')),
            'largest_collection' => $largestCollection ? [
                'cari' => $largestCollection['current_account_name'],
                'amount_formatted' => $largestCollection['amount_in_formatted'],
            ] : null,
            'largest_payment' => $largestPayment ? [
                'cari' => $largestPayment['current_account_name'],
                'amount_formatted' => $largestPayment['amount_out_formatted'],
            ] : null,
            'recent_today' => $todayRows->sortByDesc('occurred_at')->take(6)->values()->all(),
        ];
    }
}
