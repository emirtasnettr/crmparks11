<?php

namespace App\Modules\Finance\Data;

use App\Support\DemoData;
use App\Core\Helpers\MoneyCalculator;

use App\Modules\Business\Data\BusinessDummyData;
use App\Modules\Business\Data\BusinessEarningDummyData;
use Carbon\Carbon;

class FinanceInvoiceDummyData
{
    private const REFERENCE_DATE = '2026-07-07';

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $recordsCache = null;

    /**
     * @return array<string, string>
     */
    public static function invoiceTypes(): array
    {
        return [
            'e_invoice' => 'e-Fatura',
            'e_archive' => 'e-Arşiv',
            'manual' => 'Manuel',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function invoiceStatuses(): array
    {
        return [
            'issued' => 'Kesildi',
            'draft' => 'Taslak',
            'cancelled' => 'İptal',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function collectionStatuses(): array
    {
        return [
            'collected' => 'Tahsil Edildi',
            'partial' => 'Kısmi Tahsil',
            'pending' => 'Bekliyor',
            'overdue' => 'Vadesi Geçti',
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
     * @return array<int, array{id: int, reference: string, business_id: int, period_label: string}>
     */
    public static function earningOptions(): array
    {
        $usedEarningIds = collect(self::records())
            ->pluck('earning_id')
            ->filter()
            ->all();

        return collect(BusinessEarningDummyData::all())
            ->reject(fn (array $earning) => in_array($earning['id'], $usedEarningIds, true))
            ->map(fn (array $earning) => [
                'id' => $earning['id'],
                'reference' => sprintf('ISH-%06d', $earning['id']),
                'business_id' => $earning['business_id'],
                'period_label' => $earning['period_label'],
                'amount' => $earning['revenue'],
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
            ->sortByDesc('invoice_date')
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
            ->filter(function (array $invoice) use ($filters, $reference) {
                if (($filters['business_id'] ?? 'all') !== 'all' && (int) $invoice['business_id'] !== (int) $filters['business_id']) {
                    return false;
                }

                if (($filters['invoice_type'] ?? 'all') !== 'all' && $invoice['invoice_type'] !== $filters['invoice_type']) {
                    return false;
                }

                if (($filters['invoice_status'] ?? 'all') !== 'all' && $invoice['invoice_status'] !== $filters['invoice_status']) {
                    return false;
                }

                if (($filters['collection_status'] ?? 'all') !== 'all' && $invoice['collection_status'] !== $filters['collection_status']) {
                    return false;
                }

                $date = Carbon::parse($invoice['invoice_date']);
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

        $active = collect($items)->where('invoice_status', '!=', 'cancelled');

        $thisMonth = $active->filter(
            fn (array $i) => Carbon::parse($i['invoice_date'])->month === $reference->month
                && Carbon::parse($i['invoice_date'])->year === $reference->year
                && $i['invoice_status'] === 'issued'
        );

        return [
            'total_invoice' => round($active->sum('subtotal'), 2),
            'this_month_issued' => round($thisMonth->sum('subtotal'), 2),
            'collected_amount' => round($active->sum('collected_amount'), 2),
            'pending_amount' => round($active->whereIn('collection_status', ['pending', 'partial', 'overdue'])->sum('remaining_amount'), 2),
            'cancelled_count' => collect($items)->where('invoice_status', 'cancelled')->count(),
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
        $earnings = BusinessEarningDummyData::all();
        $typePlan = array_merge(
            array_fill(0, 35, 'e_invoice'),
            array_fill(0, 30, 'e_archive'),
            array_fill(0, 20, 'manual'),
        );
        $statusPlan = array_merge(
            array_fill(0, 55, 'issued'),
            array_fill(0, 15, 'draft'),
            array_fill(0, 15, 'cancelled'),
        );
        $collectionPlan = array_merge(
            array_fill(0, 25, 'collected'),
            array_fill(0, 18, 'partial'),
            array_fill(0, 27, 'pending'),
            array_fill(0, 15, 'overdue'),
        );

        $records = [];
        $usedEarningIds = [];

        foreach ($typePlan as $index => $invoiceType) {
            $id = $index + 1;
            $invoiceStatus = $statusPlan[$index];
            $collectionStatus = $invoiceStatus === 'cancelled' ? 'pending' : $collectionPlan[$index];
            $business = $businesses[$index % count($businesses)];

            $hasEarning = $id <= count($earnings) && $invoiceType !== 'manual' && $id % 5 !== 0;
            $earning = $hasEarning ? $earnings[$id - 1] : null;

            if ($earning && in_array($earning['id'], $usedEarningIds, true)) {
                $earning = null;
                $hasEarning = false;
            }

            if ($earning) {
                $usedEarningIds[] = $earning['id'];
            }

            $subtotal = $earning
                ? round((float) $earning['revenue'], 2)
                : round(15000 + (($id * 1847) % 240000), 2);
            $vatRate = $id % 4 === 0 ? 10 : 20;
            $vatAmount = round($subtotal * ($vatRate / 100), 2);
            $grandTotal = round($subtotal + $vatAmount, 2);

            $invoiceDate = Carbon::parse('2026-01-10')->addDays($id * 2 + ($id % 3));
            $dueDate = $invoiceDate->copy()->addDays(15 + ($id % 20));

            if ($collectionStatus === 'overdue') {
                $dueDate = Carbon::parse(self::REFERENCE_DATE)->subDays(3 + ($id % 12));
            }

            if ($invoiceStatus === 'draft') {
                $invoiceDate = Carbon::parse(self::REFERENCE_DATE)->subDays($id % 5);
            }

            $collectedAmount = match ($collectionStatus) {
                'collected' => $invoiceStatus === 'issued' ? $subtotal : 0,
                'partial' => $invoiceStatus === 'issued' ? round($subtotal * (0.45 + ($id % 3) * 0.12), 2) : 0,
                default => 0,
            };

            if (in_array($invoiceStatus, ['draft', 'cancelled'], true)) {
                $collectedAmount = 0;
                $collectionStatus = 'pending';
            }

            $records[] = [
                'id' => $id,
                'reference' => sprintf('FTR-2026-%06d', $id),
                'business_id' => $earning['business_id'] ?? $business['id'],
                'earning_id' => $earning['id'] ?? null,
                'earning_reference' => $earning ? sprintf('ISH-%06d', $earning['id']) : null,
                'earning_period' => $earning['period_label'] ?? null,
                'invoice_type' => $invoiceType,
                'invoice_status' => $invoiceStatus,
                'collection_status' => $collectionStatus,
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'subtotal' => $subtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'grand_total' => $grandTotal,
                'collected_amount' => $collectedAmount,
                'collection_id' => $collectedAmount > 0 ? $id : null,
                'source' => $hasEarning ? 'earning' : 'manual',
                'current_account_id' => ($earning['business_id'] ?? $business['id']),
                'current_account_code' => sprintf('CAR-%06d', $earning['business_id'] ?? $business['id']),
                'e_invoice_uuid' => $invoiceType === 'e_invoice' && $invoiceStatus === 'issued'
                    ? sprintf('E-FATURA-%s-%06d', '2026', $id)
                    : null,
                'e_archive_uuid' => $invoiceType === 'e_archive' && $invoiceStatus === 'issued'
                    ? sprintf('E-ARSIV-%s-%06d', '2026', $id)
                    : null,
                'gib_status' => match (true) {
                    $invoiceStatus === 'draft' => 'draft',
                    $invoiceStatus === 'cancelled' => 'cancelled',
                    in_array($invoiceType, ['e_invoice', 'e_archive'], true) => 'sent',
                    default => 'not_applicable',
                },
                'pdf_filename' => $invoiceStatus !== 'draft' ? 'FTR-2026-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT).'.pdf' : null,
                'description' => $hasEarning
                    ? 'Hakediş dönemi faturası — '.($earning['period_label'] ?? '')
                    : 'Manuel fatura kaydı',
                'notes' => $id % 8 === 0 ? 'Muhasebe onayı tamamlandı.' : null,
                'created_at' => $invoiceDate->copy()->subDay()->toDateString(),
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
        $remaining = round($row['subtotal'] - $row['collected_amount'], 2);

        $enriched = array_merge($row, [
            'business_name' => $business['company_name'] ?? '—',
            'business_brand' => $business['brand_name'] ?? '—',
            'business_tax_no' => $business['tax_number'] ?? '—',
            'business_address' => trim(($business['address'] ?? '').', '.($business['city'] ?? '')),
            'invoice_type_label' => self::invoiceTypes()[$row['invoice_type']] ?? $row['invoice_type'],
            'invoice_status_label' => self::invoiceStatuses()[$row['invoice_status']] ?? $row['invoice_status'],
            'collection_status_label' => self::collectionStatuses()[$row['collection_status']] ?? $row['collection_status'],
            'source_label' => $row['source'] === 'earning' ? 'Hakediş' : 'Manuel',
            'subtotal_formatted' => self::formatMoney($row['subtotal']),
            'vat_amount_formatted' => MoneyCalculator::formatVatAmount($row['vat_amount']),
            'grand_total_formatted' => MoneyCalculator::formatIncludingVat($row['grand_total']),
            'collected_amount_formatted' => self::formatMoney($row['collected_amount']),
            'remaining_amount' => $remaining,
            'remaining_amount_formatted' => self::formatMoney($remaining),
            'invoice_date_formatted' => Carbon::parse($row['invoice_date'])->format('d.m.Y'),
            'due_date_formatted' => Carbon::parse($row['due_date'])->format('d.m.Y'),
            'created_at_formatted' => Carbon::parse($row['created_at'])->format('d.m.Y'),
            'earning_period_display' => $row['earning_period'] ?? '—',
            'earning_reference_display' => $row['earning_reference'] ?? '—',
        ]);

        if ($detailed) {
            $enriched['earning_info'] = $row['earning_id'] ? [
                'id' => $row['earning_id'],
                'reference' => $row['earning_reference'],
                'period' => $row['earning_period'],
                'amount_formatted' => $enriched['subtotal_formatted'],
            ] : null;

            $enriched['collection_info'] = $row['collection_id'] ? [
                'id' => $row['collection_id'],
                'reference' => sprintf('TAH-2026-%06d', $row['collection_id']),
                'status' => $enriched['collection_status_label'],
                'collected_formatted' => $enriched['collected_amount_formatted'],
                'remaining_formatted' => $enriched['remaining_amount_formatted'],
            ] : null;

            $enriched['current_account_movements'] = $row['invoice_status'] === 'issued' ? [
                [
                    'code' => $row['current_account_code'],
                    'document_no' => $row['reference'],
                    'date' => $enriched['invoice_date_formatted'],
                    'type_label' => 'Gelir Faturası',
                    'debit' => 0,
                    'credit' => $row['subtotal'],
                    'debit_formatted' => '—',
                    'credit_formatted' => self::formatMoney($row['subtotal']),
                    'description' => 'Fatura: '.$row['reference'],
                ],
            ] : [];

            if ($row['collected_amount'] > 0) {
                $enriched['current_account_movements'][] = [
                    'code' => $row['current_account_code'],
                    'document_no' => sprintf('TAH-2026-%06d', $row['collection_id']),
                    'date' => $enriched['due_date_formatted'],
                    'type_label' => 'Tahsilat',
                    'debit' => $row['collected_amount'],
                    'credit' => 0,
                    'debit_formatted' => self::formatMoney($row['collected_amount']),
                    'credit_formatted' => '—',
                    'description' => 'Tahsilat: '.sprintf('TAH-2026-%06d', $row['collection_id']),
                ];
            }

            $enriched['integration_info'] = [
                'type' => $enriched['invoice_type_label'],
                'uuid' => $row['e_invoice_uuid'] ?? $row['e_archive_uuid'] ?? '—',
                'gib_status' => match ($row['gib_status']) {
                    'sent' => 'GİB\'e Gönderildi',
                    'draft' => 'Taslak',
                    'cancelled' => 'İptal Edildi',
                    default => 'Uygulanmaz',
                },
            ];
        }

        return $enriched;
    }

    private static function formatMoney(float $amount): string
    {
        return MoneyCalculator::format($amount);
    }
}
