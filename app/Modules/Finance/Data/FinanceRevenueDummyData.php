<?php

namespace App\Modules\Finance\Data;

use App\Support\DemoData;
use App\Core\Helpers\MoneyCalculator;

use App\Modules\Business\Data\BusinessDummyData;
use Carbon\Carbon;

class FinanceRevenueDummyData
{
    private const REFERENCE_DATE = '2026-07-07';

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $recordsCache = null;

    /**
     * @return array<string, string>
     */
    public static function revenueTypes(): array
    {
        return [
            'per_package' => 'Paket Başı Hizmet',
            'fixed_monthly' => 'Aylık Sabit Hizmet',
            'extra_service' => 'Ek Hizmet',
            'penalty' => 'Ceza Bedeli',
            'manual' => 'Manuel Gelir',
            'other' => 'Diğer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function collectionStatuses(): array
    {
        return [
            'collected' => 'Tahsil Edildi',
            'pending' => 'Bekliyor',
            'overdue' => 'Gecikmiş',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function invoiceStatuses(): array
    {
        return [
            'issued' => 'Kesildi',
            'pending' => 'Bekliyor',
            'none' => 'Fatura Yok',
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
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(self::records())
            ->map(fn (array $row) => self::enrich($row))
            ->sortByDesc('revenue_date')
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
            ->filter(function (array $revenue) use ($filters, $reference) {
                if (($filters['business_id'] ?? 'all') !== 'all' && (int) $revenue['business_id'] !== (int) $filters['business_id']) {
                    return false;
                }

                if (($filters['revenue_type'] ?? 'all') !== 'all' && $revenue['revenue_type'] !== $filters['revenue_type']) {
                    return false;
                }

                if (($filters['collection_status'] ?? 'all') !== 'all' && $revenue['collection_status'] !== $filters['collection_status']) {
                    return false;
                }

                if (($filters['invoice_status'] ?? 'all') !== 'all' && $revenue['invoice_status'] !== $filters['invoice_status']) {
                    return false;
                }

                $date = Carbon::parse($revenue['revenue_date']);
                $range = $filters['date_range'] ?? 'all';

                if ($range === 'today' && ! $date->isSameDay($reference)) {
                    return false;
                }

                if ($range === 'week' && ($date->lt($reference->copy()->startOfWeek()) || $date->gt($reference->copy()->endOfWeek()))) {
                    return false;
                }

                if ($range === 'month' && ($date->month !== $reference->month || $date->year !== $reference->year)) {
                    return false;
                }

                if ($range === 'year' && $date->year !== $reference->year) {
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

        $thisMonth = collect($items)->filter(
            fn (array $r) => Carbon::parse($r['revenue_date'])->month === $reference->month
                && Carbon::parse($r['revenue_date'])->year === $reference->year
        );

        $collected = collect($items)->where('collection_status', 'collected');
        $pending = collect($items)->whereIn('collection_status', ['pending', 'overdue']);
        $businessCount = collect($items)->pluck('business_id')->unique()->count();

        return [
            'total_revenue' => round(collect($items)->sum('amount'), 2),
            'this_month_revenue' => round($thisMonth->sum('amount'), 2),
            'collected_amount' => round($collected->sum('amount'), 2),
            'pending_collection' => round($pending->sum('amount'), 2),
            'average_per_business' => $businessCount > 0
                ? round(collect($items)->sum('amount') / $businessCount, 2)
                : 0,
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
        $types = array_keys(self::revenueTypes());
        $collectionCycle = ['collected', 'collected', 'pending', 'overdue', 'collected', 'pending'];
        $invoiceCycle = ['issued', 'issued', 'pending', 'none', 'issued'];
        $months = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz',
        ];

        $records = [];

        for ($id = 1; $id <= 55; $id++) {
            $business = $businesses[($id - 1) % count($businesses)];
            $type = $types[$id % count($types)];
            $collectionStatus = $collectionCycle[$id % count($collectionCycle)];
            $invoiceStatus = $invoiceCycle[$id % count($invoiceCycle)];
            $periodMonth = (($id + 4) % 7) + 1;
            $periodYear = 2026;
            $amount = round(8500 + (($id * 1737) % 185000), 2);
            $vatRate = $id % 4 === 0 ? 10 : 20;
            $revenueDate = Carbon::parse('2026-01-10')->addDays($id * 3 + ($id % 5));
            $createdAt = $revenueDate->copy()->addDays(1);
            $hasEarning = $id % 3 !== 0;
            $hasInvoice = $invoiceStatus !== 'none';

            $collectionDate = match ($collectionStatus) {
                'collected' => $revenueDate->copy()->addDays(5 + ($id % 12))->toDateString(),
                'overdue' => null,
                default => null,
            };

            $records[] = [
                'id' => $id,
                'reference' => sprintf('GLR-2026-%06d', $id),
                'business_id' => $business['id'],
                'revenue_type' => $type,
                'period_month' => $hasEarning ? $periodMonth : null,
                'period_year' => $hasEarning ? $periodYear : null,
                'period_label' => $hasEarning ? ($months[$periodMonth] ?? '').' '.$periodYear : null,
                'invoice_no' => $hasInvoice ? sprintf('FTR-2026-%04d', 800 + $id) : null,
                'invoice_status' => $invoiceStatus,
                'amount' => $amount,
                'vat_rate' => $vatRate,
                'collection_status' => $collectionStatus,
                'collection_date' => $collectionDate,
                'revenue_date' => $revenueDate->toDateString(),
                'created_at' => $createdAt->toDateString(),
                'description' => self::descriptionForType($type, $business['brand_name']),
                'earning_id' => $hasEarning ? 100 + $id : null,
                'earning_reference' => $hasEarning ? sprintf('IHK-2026-%04d', 200 + $id) : null,
                'current_account_id' => $business['id'],
                'current_account_code' => sprintf('CAR-%06d', $business['id']),
                'notes' => $id % 5 === 0 ? 'Muhasebe onayı tamamlandı.' : null,
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
        $vatAmount = round($row['amount'] * ($row['vat_rate'] / 100), 2);
        $grossAmount = round($row['amount'] + $vatAmount, 2);

        $enriched = array_merge($row, [
            'business_name' => $business['company_name'] ?? '—',
            'business_brand' => $business['brand_name'] ?? '—',
            'business_phone' => $business['phone'] ?? '—',
            'business_city' => $business['city'] ?? '—',
            'business_district' => $business['district'] ?? '—',
            'revenue_type_label' => self::revenueTypes()[$row['revenue_type']] ?? $row['revenue_type'],
            'collection_status_label' => self::collectionStatuses()[$row['collection_status']] ?? $row['collection_status'],
            'invoice_status_label' => self::invoiceStatuses()[$row['invoice_status']] ?? $row['invoice_status'],
            'amount_formatted' => self::formatMoney($row['amount']),
            'vat_amount' => $vatAmount,
            'vat_amount_formatted' => MoneyCalculator::formatVatAmount($vatAmount),
            'gross_amount' => $grossAmount,
            'gross_amount_formatted' => MoneyCalculator::formatIncludingVat($grossAmount),
            'revenue_date_formatted' => Carbon::parse($row['revenue_date'])->format('d.m.Y'),
            'created_at_formatted' => Carbon::parse($row['created_at'])->format('d.m.Y'),
            'collection_date_formatted' => $row['collection_date']
                ? Carbon::parse($row['collection_date'])->format('d.m.Y')
                : '—',
            'period_display' => $row['period_label'] ?? '—',
            'invoice_no_display' => $row['invoice_no'] ?? '—',
        ]);

        if ($detailed) {
            $enriched['current_account_movement'] = [
                'document_no' => $row['invoice_no'] ?? $row['reference'],
                'date' => $enriched['revenue_date_formatted'],
                'type_label' => $row['collection_status'] === 'collected' ? 'Tahsilat' : 'Fatura',
                'debit' => $row['collection_status'] === 'collected' ? 0 : $row['amount'],
                'credit' => $row['collection_status'] === 'collected' ? $row['amount'] : 0,
                'debit_formatted' => $row['collection_status'] === 'collected' ? '—' : self::formatMoney($row['amount']),
                'credit_formatted' => $row['collection_status'] === 'collected' ? self::formatMoney($row['amount']) : '—',
                'description' => 'Gelir kaydı: '.$row['reference'],
            ];

            $enriched['collection_info'] = [
                'status' => $row['collection_status'],
                'status_label' => $enriched['collection_status_label'],
                'date' => $enriched['collection_date_formatted'],
                'method' => $row['collection_status'] === 'collected' ? 'Banka Havalesi' : '—',
                'reference' => $row['collection_status'] === 'collected'
                    ? 'THS-2026-'.str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT)
                    : '—',
            ];

            $enriched['invoice_info'] = [
                'status' => $row['invoice_status'],
                'status_label' => $enriched['invoice_status_label'],
                'invoice_no' => $enriched['invoice_no_display'],
                'issue_date' => $row['invoice_no'] ? $enriched['revenue_date_formatted'] : '—',
                'due_date' => $row['invoice_no']
                    ? Carbon::parse($row['revenue_date'])->addDays(15)->format('d.m.Y')
                    : '—',
            ];

            $enriched['earning_info'] = $row['earning_id'] ? [
                'id' => $row['earning_id'],
                'reference' => $row['earning_reference'],
                'period' => $row['period_label'],
                'amount_formatted' => $enriched['amount_formatted'],
            ] : null;
        }

        return $enriched;
    }

    private static function descriptionForType(string $type, string $brand): string
    {
        return match ($type) {
            'per_package' => $brand.' paket başı hizmet geliri',
            'fixed_monthly' => $brand.' aylık sabit hizmet bedeli',
            'extra_service' => $brand.' ek operasyon hizmeti',
            'penalty' => $brand.' ceza bedeli tahakkuku',
            'manual' => 'Manuel gelir kaydı — '.$brand,
            default => $brand.' diğer gelir kalemi',
        };
    }

    private static function formatMoney(float $amount): string
    {
        return MoneyCalculator::format($amount);
    }
}
