<?php

namespace App\Modules\Finance\Data;

use App\Core\Helpers\MoneyCalculator;

use App\Modules\Business\Data\BusinessDummyData;
use Carbon\Carbon;

class FinanceCollectionDummyData
{
    private const REFERENCE_DATE = '2026-07-07';

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $recordsCache = null;

    /**
     * @return array<string, string>
     */
    public static function collectionStatuses(): array
    {
        return [
            'collected' => 'Tahsil Edildi',
            'partial' => 'Kısmi Tahsil Edildi',
            'pending' => 'Bekliyor',
            'overdue' => 'Vadesi Geçti',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paymentMethods(): array
    {
        return [
            'bank_transfer' => 'Banka Havalesi',
            'eft' => 'EFT',
            'fast' => 'FAST',
            'cash' => 'Nakit',
            'credit_card' => 'Kredi Kartı',
            'offset' => 'Mahsup',
            'other' => 'Diğer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dateRanges(): array
    {
        return [
            'all' => 'Tümü',
            'today' => 'Bugün',
            'week' => 'Bu Hafta',
            'month' => 'Bu Ay',
            'year' => 'Bu Yıl',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dueDateFilters(): array
    {
        return [
            'all' => 'Tümü',
            'overdue' => 'Vadesi Geçen',
            'today' => 'Bugün Vadeli',
            'week' => 'Bu Hafta Vadeli',
            'month' => 'Bu Ay Vadeli',
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function businesses(): array
    {
        return collect(BusinessDummyData::all())
            ->map(fn (array $business) => [
                'id' => $business['id'],
                'name' => $business['company_name'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, reference: string}>
     */
    public static function revenueOptions(): array
    {
        return collect(FinanceRevenueDummyData::all())
            ->take(30)
            ->map(fn (array $revenue) => [
                'id' => $revenue['id'],
                'reference' => $revenue['reference'],
                'business_id' => $revenue['business_id'],
                'invoice_no' => $revenue['invoice_no'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return collect(self::records())
            ->map(fn (array $row) => self::enrich($row))
            ->sortByDesc('due_date')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        $reference = Carbon::parse(self::REFERENCE_DATE);

        return collect(self::all())
            ->filter(function (array $collection) use ($filters, $reference) {
                if (($filters['business_id'] ?? 'all') !== 'all' && (int) $collection['business_id'] !== (int) $filters['business_id']) {
                    return false;
                }

                if (($filters['collection_status'] ?? 'all') !== 'all' && $collection['status'] !== $filters['collection_status']) {
                    return false;
                }

                if (($filters['payment_method'] ?? 'all') !== 'all' && $collection['payment_method'] !== $filters['payment_method']) {
                    return false;
                }

                $collectionDate = $collection['collection_date']
                    ? Carbon::parse($collection['collection_date'])
                    : null;
                $range = $filters['date_range'] ?? 'all';

                if ($range !== 'all' && $collectionDate) {
                    if ($range === 'today' && ! $collectionDate->isSameDay($reference)) {
                        return false;
                    }

                    if ($range === 'week' && ($collectionDate->lt($reference->copy()->startOfWeek()) || $collectionDate->gt($reference->copy()->endOfWeek()))) {
                        return false;
                    }

                    if ($range === 'month' && ($collectionDate->month !== $reference->month || $collectionDate->year !== $reference->year)) {
                        return false;
                    }

                    if ($range === 'year' && $collectionDate->year !== $reference->year) {
                        return false;
                    }
                } elseif ($range !== 'all' && ! $collectionDate) {
                    return false;
                }

                $dueDate = Carbon::parse($collection['due_date']);
                $dueFilter = $filters['due_date'] ?? 'all';

                if ($dueFilter === 'overdue' && $collection['status'] !== 'overdue') {
                    return false;
                }

                if ($dueFilter === 'today' && ! $dueDate->isSameDay($reference)) {
                    return false;
                }

                if ($dueFilter === 'week' && ($dueDate->lt($reference->copy()->startOfWeek()) || $dueDate->gt($reference->copy()->endOfWeek()))) {
                    return false;
                }

                if ($dueFilter === 'month' && ($dueDate->month !== $reference->month || $dueDate->year !== $reference->year)) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, float|int>
     */
    public static function summarize(array $filters): array
    {
        $items = self::filter($filters);
        $reference = Carbon::parse(self::REFERENCE_DATE);

        $todayCollected = collect($items)->filter(
            fn (array $c) => $c['collection_date'] && Carbon::parse($c['collection_date'])->isSameDay($reference)
        )->sum('collected_amount');

        $monthCollected = collect($items)->filter(
            fn (array $c) => $c['collection_date']
                && Carbon::parse($c['collection_date'])->month === $reference->month
                && Carbon::parse($c['collection_date'])->year === $reference->year
        )->sum('collected_amount');

        return [
            'total_amount' => round(collect($items)->sum('total_amount'), 2),
            'collected_amount' => round(collect($items)->sum('collected_amount'), 2),
            'pending_amount' => round(collect($items)->whereIn('status', ['pending', 'partial'])->sum('remaining_amount'), 2),
            'overdue_amount' => round(collect($items)->where('status', 'overdue')->sum('remaining_amount'), 2),
            'today_collected' => round($todayCollected, 2),
            'month_collected' => round($monthCollected, 2),
        ];
    }

    public static function find(int $id): ?array
    {
        $row = collect(self::records())->firstWhere('id', $id);

        return $row ? self::enrich($row, true) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function records(): array
    {
        if (self::$recordsCache !== null) {
            return self::$recordsCache;
        }

        $businesses = BusinessDummyData::all();
        $methods = array_keys(self::paymentMethods());
        $statusPlan = array_merge(
            array_fill(0, 20, 'collected'),
            array_fill(0, 15, 'partial'),
            array_fill(0, 15, 'pending'),
            array_fill(0, 15, 'overdue'),
        );

        $records = [];

        foreach ($statusPlan as $index => $status) {
            $id = $index + 1;
            $business = $businesses[$index % count($businesses)];
            $totalAmount = round(12000 + (($id * 1621) % 210000), 2);
            $method = $methods[$id % count($methods)];
            $dueDate = Carbon::parse('2026-05-01')->addDays($id * 2);
            $hasRevenue = $id % 4 !== 0;
            $revenueId = $hasRevenue ? (($id % 30) + 1) : null;

            [$collectedAmount, $collectionDate] = match ($status) {
                'collected' => [$totalAmount, $dueDate->copy()->subDays(2)->addDays($id % 5)->toDateString()],
                'partial' => [round($totalAmount * (0.35 + ($id % 4) * 0.12), 2), $dueDate->copy()->subDays(5)->toDateString()],
                'overdue' => [0, null],
                default => [0, null],
            };

            if ($status === 'pending') {
                $dueDate = Carbon::parse(self::REFERENCE_DATE)->addDays(3 + ($id % 20));
            }

            if ($status === 'overdue') {
                $dueDate = Carbon::parse(self::REFERENCE_DATE)->subDays(5 + ($id % 25));
            }

            $records[] = [
                'id' => $id,
                'reference' => sprintf('TAH-2026-%06d', $id),
                'business_id' => $business['id'],
                'revenue_id' => $revenueId,
                'revenue_reference' => $revenueId ? sprintf('GLR-2026-%06d', $revenueId) : null,
                'invoice_no' => sprintf('FTR-2026-%04d', 900 + $id),
                'due_date' => $dueDate->toDateString(),
                'collection_date' => $collectionDate,
                'total_amount' => $totalAmount,
                'collected_amount' => $collectedAmount,
                'payment_method' => $collectedAmount > 0 ? $method : null,
                'payment_reference' => $collectedAmount > 0 ? 'REF-2026-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT) : null,
                'bank' => $collectedAmount > 0 ? ['Garanti BBVA', 'İş Bankası', 'Ziraat Bankası', 'Akbank'][$id % 4] : null,
                'status' => $status,
                'source' => $hasRevenue ? 'revenue' : ($id % 7 === 0 ? 'earning' : 'manual'),
                'current_account_id' => $business['id'],
                'current_account_code' => sprintf('CAR-%06d', $business['id']),
                'created_at' => $dueDate->copy()->subDays(10)->toDateString(),
                'description' => 'İşletme tahsilat kaydı — '.$business['brand_name'],
                'notes' => $id % 5 === 0 ? 'Muhasebe tarafından kontrol edildi.' : null,
            ];
        }

        self::$recordsCache = $records;

        return self::$recordsCache;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function enrich(array $row, bool $detailed = false): array
    {
        $business = collect(BusinessDummyData::all())->firstWhere('id', $row['business_id']);
        $remaining = round($row['total_amount'] - $row['collected_amount'], 2);

        $enriched = array_merge($row, [
            'business_name' => $business['company_name'] ?? '—',
            'business_brand' => $business['brand_name'] ?? '—',
            'business_phone' => $business['phone'] ?? '—',
            'business_city' => $business['city'] ?? '—',
            'status_label' => self::collectionStatuses()[$row['status']] ?? $row['status'],
            'payment_method_label' => $row['payment_method']
                ? (self::paymentMethods()[$row['payment_method']] ?? $row['payment_method'])
                : '—',
            'total_amount_formatted' => self::formatMoney($row['total_amount']),
            'collected_amount_formatted' => self::formatMoney($row['collected_amount']),
            'remaining_amount' => $remaining,
            'remaining_amount_formatted' => self::formatMoney($remaining),
            'due_date_formatted' => Carbon::parse($row['due_date'])->format('d.m.Y'),
            'collection_date_formatted' => $row['collection_date']
                ? Carbon::parse($row['collection_date'])->format('d.m.Y')
                : '—',
            'created_at_formatted' => Carbon::parse($row['created_at'])->format('d.m.Y'),
            'revenue_reference_display' => $row['revenue_reference'] ?? '—',
            'invoice_no_display' => $row['invoice_no'] ?? '—',
        ]);

        if ($detailed) {
            $enriched['collection_history'] = self::buildCollectionHistory($row, $remaining);
            $enriched['receipts'] = self::buildReceipts($row);
            $enriched['revenue_info'] = $row['revenue_id'] ? [
                'id' => $row['revenue_id'],
                'reference' => $row['revenue_reference'],
                'invoice_no' => $row['invoice_no'],
                'amount_formatted' => $enriched['total_amount_formatted'],
            ] : null;
            $enriched['invoice_info'] = [
                'invoice_no' => $row['invoice_no'],
                'due_date' => $enriched['due_date_formatted'],
                'total_formatted' => $enriched['total_amount_formatted'],
            ];
            $enriched['current_account_movement'] = $row['collected_amount'] > 0 ? [
                'code' => $row['current_account_code'],
                'document_no' => $row['payment_reference'] ?? $row['reference'],
                'date' => $enriched['collection_date_formatted'],
                'type_label' => 'Tahsilat',
                'debit' => 0,
                'credit' => $row['collected_amount'],
                'debit_formatted' => '—',
                'credit_formatted' => self::formatMoney($row['collected_amount']),
                'description' => 'Tahsilat: '.$row['reference'],
            ] : null;
        }

        return $enriched;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array<string, mixed>>
     */
    private static function buildCollectionHistory(array $row, float $remaining): array
    {
        if ($row['collected_amount'] <= 0) {
            return [];
        }

        if ($row['status'] === 'partial') {
            $first = round($row['collected_amount'] * 0.55, 2);
            $second = round($row['collected_amount'] - $first, 2);

            return [
                self::historyEntry(1, $row, $first, Carbon::parse($row['collection_date'])->subDays(7)->toDateString()),
                self::historyEntry(2, $row, $second, $row['collection_date']),
            ];
        }

        return [self::historyEntry(1, $row, $row['collected_amount'], $row['collection_date'])];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function historyEntry(int $seq, array $row, float $amount, ?string $date): array
    {
        return [
            'id' => $seq,
            'date' => $date ? Carbon::parse($date)->format('d.m.Y') : '—',
            'amount' => $amount,
            'amount_formatted' => self::formatMoney($amount),
            'method' => self::paymentMethods()[$row['payment_method']] ?? '—',
            'reference' => ($row['payment_reference'] ?? 'REF').'-'.$seq,
            'bank' => $row['bank'] ?? '—',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array<string, string>>
     */
    private static function buildReceipts(array $row): array
    {
        if ($row['collected_amount'] <= 0) {
            return [];
        }

        $items = [
            ['name' => 'Dekont-'.$row['reference'].'.pdf', 'type' => 'Banka Dekontu', 'date' => Carbon::parse($row['collection_date'] ?? $row['created_at'])->format('d.m.Y')],
        ];

        if ($row['status'] === 'partial') {
            $items[] = ['name' => 'Dekont-'.$row['reference'].'-2.pdf', 'type' => 'Kısmi Tahsilat', 'date' => Carbon::parse($row['collection_date'])->format('d.m.Y')];
        }

        return $items;
    }

    private static function formatMoney(float $amount): string
    {
        return MoneyCalculator::format($amount);
    }
}
