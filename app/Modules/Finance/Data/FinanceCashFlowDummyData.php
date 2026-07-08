<?php

namespace App\Modules\Finance\Data;

use App\Core\Helpers\MoneyCalculator;

use App\Modules\Agency\Data\AgencyDummyData;
use App\Modules\Business\Data\BusinessDummyData;
use App\Modules\Courier\Data\CourierDummyData;
use Carbon\Carbon;

class FinanceCashFlowDummyData
{
    private const REFERENCE_DATE = '2026-07-07';

    private const OPENING_BALANCE = 2_450_000.00;

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $recordsCache = null;

    /**
     * @return array<string, string>
     */
    public static function periods(): array
    {
        return [
            'today' => 'Bugün',
            'week' => 'Bu Hafta',
            'month' => 'Bu Ay',
            'year' => 'Bu Yıl',
            'custom' => 'Özel Tarih Aralığı',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function transactionTypes(): array
    {
        return [
            'collection' => 'Tahsilat',
            'payment' => 'Ödeme',
            'revenue' => 'Gelir',
            'expense' => 'Gider',
            'cash_in' => 'Manuel Nakit Girişi',
            'cash_out' => 'Manuel Nakit Çıkışı',
            'offset' => 'Mahsup',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sourceTypes(): array
    {
        return [
            'revenue' => 'Gelir',
            'expense' => 'Gider',
            'collection' => 'Tahsilat',
            'payment' => 'Ödeme',
            'earning' => 'Hakediş',
            'invoice' => 'Fatura',
            'manual' => 'Manuel',
        ];
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return array<string, mixed>
     */
    public static function analyze(array $filters): array
    {
        $reference = Carbon::parse(self::REFERENCE_DATE);
        [$start, $end] = self::resolvePeriodRange($filters, $reference);

        $allWithBalance = self::recordsWithBalance();
        $filtered = self::filterByPeriod($allWithBalance, $start, $end);

        $previousStart = $start->copy()->subDays($start->diffInDays($end) + 1);
        $previousEnd = $start->copy()->subDay();
        $previousFiltered = self::filterByPeriod($allWithBalance, $previousStart, $previousEnd);

        $kpis = self::buildKpis($filtered, $previousFiltered);
        $charts = self::buildCharts($filtered, $start, $end);
        $sidebar = self::buildSidebar($allWithBalance, $reference);

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
    private static function recordsWithBalance(): array
    {
        if (self::$recordsCache !== null) {
            return self::$recordsCache;
        }

        $businesses = BusinessDummyData::all();
        $agencies = AgencyDummyData::all();
        $couriers = CourierDummyData::raw();
        $users = ['Mehmet Kaya', 'Zeynep Arslan', 'Ayşe Demir', 'Can Öztürk', 'Elif Şahin'];

        $typeCycle = [
            ['type' => 'collection', 'source' => 'collection', 'module' => 'collections', 'prefix' => 'TAH'],
            ['type' => 'payment', 'source' => 'payment', 'module' => 'payments', 'prefix' => 'ODM'],
            ['type' => 'revenue', 'source' => 'revenue', 'module' => 'revenues', 'prefix' => 'GLR'],
            ['type' => 'expense', 'source' => 'expense', 'module' => 'expenses', 'prefix' => 'GDR'],
            ['type' => 'collection', 'source' => 'invoice', 'module' => 'invoices', 'prefix' => 'FTR'],
            ['type' => 'payment', 'source' => 'earning', 'module' => 'earnings', 'prefix' => 'ISH'],
            ['type' => 'cash_in', 'source' => 'manual', 'module' => 'manual', 'prefix' => 'MNK'],
            ['type' => 'cash_out', 'source' => 'manual', 'module' => 'manual', 'prefix' => 'MNK'],
            ['type' => 'offset', 'source' => 'manual', 'module' => 'current_accounts', 'prefix' => 'MHS'],
        ];

        $records = [];
        $startDate = Carbon::parse('2026-04-01 08:00:00');

        for ($id = 1; $id <= 160; $id++) {
            $config = $typeCycle[($id - 1) % count($typeCycle)];
            $occurredAt = $startDate->copy()->addHours($id * 11 + ($id % 7) * 3);
            $isInflow = in_array($config['type'], ['collection', 'revenue', 'cash_in'], true);
            $amount = round(3500 + (($id * 1973) % 285000), 2);

            if ($config['type'] === 'offset') {
                $amount = round($amount * 0.35, 2);
            }

            $business = $businesses[($id - 1) % count($businesses)];
            $agency = $agencies[($id - 1) % count($agencies)];
            $courier = $couriers[($id - 1) % count($couriers)];

            $cari = match ($config['type']) {
                'payment' => trim($courier['first_name'].' '.$courier['last_name']),
                'expense' => $id % 3 === 0 ? $agency['company_name'] : trim($courier['first_name'].' '.$courier['last_name']),
                default => $business['company_name'],
            };

            $records[] = [
                'id' => $id,
                'reference' => sprintf('NKT-2026-%06d', $id),
                'occurred_at' => $occurredAt->toDateTimeString(),
                'transaction_type' => $config['type'],
                'source_type' => $config['source'],
                'source_module' => $config['module'],
                'source_id' => 100 + $id,
                'document_reference' => sprintf('%s-2026-%06d', $config['prefix'], $id),
                'current_account_id' => $business['id'],
                'current_account_name' => $cari,
                'description' => self::descriptionFor($config['type'], $cari),
                'amount_in' => $isInflow ? $amount : 0,
                'amount_out' => $isInflow ? 0 : $amount,
                'performed_by' => $users[$id % count($users)],
            ];
        }

        usort($records, fn ($a, $b) => strcmp($a['occurred_at'], $b['occurred_at']));

        $balance = self::OPENING_BALANCE;
        foreach ($records as &$record) {
            $balance = round($balance + $record['amount_in'] - $record['amount_out'], 2);
            $record['balance'] = $balance;
        }
        unset($record);

        self::$recordsCache = array_map(fn (array $row) => self::enrich($row), $records);

        return self::$recordsCache;
    }

    private static function descriptionFor(string $type, string $cari): string
    {
        return match ($type) {
            'collection' => 'İşletme tahsilatı — '.$cari,
            'payment' => 'Kurye/acente ödemesi — '.$cari,
            'revenue' => 'Gelir kaydı tahakkuku — '.$cari,
            'expense' => 'Operasyonel gider — '.$cari,
            'cash_in' => 'Manuel kasa girişi',
            'cash_out' => 'Manuel kasa çıkışı',
            'offset' => 'Cari mahsup işlemi — '.$cari,
            default => 'Nakit hareketi — '.$cari,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function enrich(array $row): array
    {
        $occurred = Carbon::parse($row['occurred_at']);

        return array_merge($row, [
            'transaction_type_label' => self::transactionTypes()[$row['transaction_type']] ?? $row['transaction_type'],
            'source_type_label' => self::sourceTypes()[$row['source_type']] ?? $row['source_type'],
            'date_formatted' => $occurred->format('d.m.Y'),
            'time_formatted' => $occurred->format('H:i'),
            'amount_in_formatted' => $row['amount_in'] > 0 ? self::formatMoney($row['amount_in']) : '—',
            'amount_out_formatted' => $row['amount_out'] > 0 ? self::formatMoney($row['amount_out']) : '—',
            'balance_formatted' => self::formatMoney($row['balance']),
        ]);
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function resolvePeriodRange(array $filters, Carbon $reference): array
    {
        $period = $filters['period'] ?? 'month';

        if ($period === 'custom' && ! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            return [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ];
        }

        return match ($period) {
            'today' => [$reference->copy()->startOfDay(), $reference->copy()->endOfDay()],
            'week' => [$reference->copy()->startOfWeek(), $reference->copy()->endOfWeek()],
            'year' => [$reference->copy()->startOfYear(), $reference->copy()->endOfYear()],
            default => [$reference->copy()->startOfMonth(), $reference->copy()->endOfMonth()],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    private static function filterByPeriod(array $records, Carbon $start, Carbon $end): array
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
    private static function buildKpis(array $items, array $previousItems): array
    {
        $cashIn = round(collect($items)->sum('amount_in'), 2);
        $cashOut = round(collect($items)->sum('amount_out'), 2);
        $netCash = round($cashIn - $cashOut, 2);

        $prevNet = round(collect($previousItems)->sum('amount_in') - collect($previousItems)->sum('amount_out'), 2);
        $changeRate = $prevNet != 0.0
            ? round((($netCash - $prevNet) / abs($prevNet)) * 100, 1)
            : ($netCash > 0 ? 100.0 : 0.0);

        return [
            'cash_in' => $cashIn,
            'cash_in_formatted' => self::formatMoney($cashIn),
            'cash_out' => $cashOut,
            'cash_out_formatted' => self::formatMoney($cashOut),
            'net_cash' => $netCash,
            'net_cash_formatted' => self::formatMoney($netCash),
            'pending_collections' => 1_245_000.00,
            'pending_collections_formatted' => self::formatMoney(1_245_000),
            'pending_payments' => 892_500.00,
            'pending_payments_formatted' => self::formatMoney(892_500),
            'cash_change_rate' => $changeRate,
            'cash_change_rate_formatted' => ($changeRate >= 0 ? '+' : '').number_format($changeRate, 1, ',', '.').'%',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private static function buildCharts(array $items, Carbon $start, Carbon $end): array
    {
        $daily = collect($items)
            ->groupBy(fn (array $row) => Carbon::parse($row['occurred_at'])->format('Y-m-d'))
            ->sortKeys();

        $labels = [];
        $balanceSeries = [];
        $dailyIn = [];
        $dailyOut = [];
        $cursor = $start->copy()->startOfDay();
        $running = self::openingBalanceBefore($start);

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
            ->map(fn ($group, $source) => [
                'label' => self::sourceTypes()[$source] ?? $source,
                'value' => (int) round($group->sum('amount_in')),
            ])
            ->values()
            ->all();

        $outflowBySource = collect($items)
            ->where('amount_out', '>', 0)
            ->groupBy('source_type')
            ->map(fn ($group, $source) => [
                'label' => self::sourceTypes()[$source] ?? $source,
                'value' => (int) round($group->sum('amount_out')),
            ])
            ->values()
            ->all();

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
                'collections' => 1_245_000,
                'payments' => 892_500,
            ],
        ];
    }

    private static function openingBalanceBefore(Carbon $date): float
    {
        $before = collect(self::recordsWithBalance())
            ->filter(fn (array $row) => Carbon::parse($row['occurred_at'])->lt($date->copy()->startOfDay()));

        if ($before->isEmpty()) {
            return self::OPENING_BALANCE;
        }

        return (float) $before->last()['balance'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $all
     * @return array<string, mixed>
     */
    private static function buildSidebar(array $all, Carbon $reference): array
    {
        $todayRows = collect($all)->filter(
            fn (array $row) => Carbon::parse($row['occurred_at'])->isSameDay($reference)
        );

        $collections = $todayRows->where('transaction_type', 'collection');
        $payments = $todayRows->where('transaction_type', 'payment');

        $largestCollection = $collections->sortByDesc('amount_in')->first();
        $largestPayment = $payments->sortByDesc('amount_out')->first();

        return [
            'today_movements' => $todayRows->count(),
            'today_collections_count' => $collections->count(),
            'today_collections_total_formatted' => self::formatMoney($collections->sum('amount_in')),
            'today_payments_count' => $payments->count(),
            'today_payments_total_formatted' => self::formatMoney($payments->sum('amount_out')),
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

    private static function formatMoney(float $amount): string
    {
        return MoneyCalculator::format($amount);
    }
}
