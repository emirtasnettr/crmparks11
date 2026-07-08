<?php

namespace App\Modules\Finance\Data;

use App\Support\DemoData;
use App\Core\Helpers\MoneyCalculator;

use App\Modules\Agency\Data\AgencyDummyData;
use App\Modules\Courier\Data\CourierDummyData;
use Carbon\Carbon;

class FinanceExpenseDummyData
{
    private const REFERENCE_DATE = '2026-07-07';

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $recordsCache = null;

    /**
     * @return array<string, string>
     */
    public static function expenseTypes(): array
    {
        return [
            'courier_earning' => 'Kurye Hakedişi',
            'agency_earning' => 'Acente Hakedişi',
            'personnel' => 'Personel',
            'fuel' => 'Yakıt',
            'office' => 'Ofis',
            'software' => 'Yazılım',
            'advertising' => 'Reklam',
            'tax' => 'Vergi',
            'rent' => 'Kira',
            'other' => 'Diğer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paymentStatuses(): array
    {
        return [
            'paid' => 'Ödendi',
            'pending' => 'Bekliyor',
            'overdue' => 'Gecikmiş',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sources(): array
    {
        return [
            'earning' => 'Hakediş',
            'manual' => 'Manuel',
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
    public static function couriers(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(CourierDummyData::raw())
            ->map(fn (array $courier) => [
                'id' => $courier['id'],
                'name' => trim($courier['first_name'].' '.$courier['last_name']),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function agencies(): array
    {
        return collect(AgencyDummyData::all())
            ->map(fn (array $agency) => [
                'id' => $agency['id'],
                'name' => $agency['company_name'],
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
            ->sortByDesc('expense_date')
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
            ->filter(function (array $expense) use ($filters, $reference) {
                if (($filters['expense_type'] ?? 'all') !== 'all' && $expense['expense_type'] !== $filters['expense_type']) {
                    return false;
                }

                if (($filters['courier_id'] ?? 'all') !== 'all' && (int) ($expense['courier_id'] ?? 0) !== (int) $filters['courier_id']) {
                    return false;
                }

                if (($filters['agency_id'] ?? 'all') !== 'all' && (int) ($expense['agency_id'] ?? 0) !== (int) $filters['agency_id']) {
                    return false;
                }

                if (($filters['payment_status'] ?? 'all') !== 'all' && $expense['payment_status'] !== $filters['payment_status']) {
                    return false;
                }

                $date = Carbon::parse($expense['expense_date']);
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
            fn (array $e) => Carbon::parse($e['expense_date'])->month === $reference->month
                && Carbon::parse($e['expense_date'])->year === $reference->year
        );

        return [
            'total_expense' => round(collect($items)->sum('amount'), 2),
            'this_month_expense' => round($thisMonth->sum('amount'), 2),
            'paid_amount' => round(collect($items)->where('payment_status', 'paid')->sum('amount'), 2),
            'pending_payment' => round(collect($items)->whereIn('payment_status', ['pending', 'overdue'])->sum('amount'), 2),
            'courier_expense' => round(collect($items)->where('expense_type', 'courier_earning')->sum('amount'), 2),
            'agency_expense' => round(collect($items)->where('expense_type', 'agency_earning')->sum('amount'), 2),
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

        $couriers = self::couriers();
        $agencies = self::agencies();
        $paymentCycle = ['paid', 'paid', 'pending', 'overdue', 'paid', 'pending'];
        $records = [];
        $id = 1;

        $distribution = array_merge(
            array_fill(0, 25, 'courier_earning'),
            array_fill(0, 15, 'agency_earning'),
            array_fill(0, 10, 'personnel'),
            array_fill(0, 5, 'fuel'),
            array_fill(0, 5, 'software'),
            array_fill(0, 5, 'advertising'),
            ['office', 'office', 'tax', 'rent', 'other'],
        );

        foreach ($distribution as $index => $type) {
            $paymentStatus = $paymentCycle[$id % count($paymentCycle)];
            $expenseDate = Carbon::parse('2026-01-08')->addDays($id * 3 + ($id % 4));
            $createdAt = $expenseDate->copy()->addDay();
            $amount = round(4200 + (($id * 1943) % 92000), 2);
            $vatRate = $id % 5 === 0 ? 10 : 20;
            $isEarningType = in_array($type, ['courier_earning', 'agency_earning'], true);

            $courierId = null;
            $agencyId = null;

            if ($type === 'courier_earning') {
                $courier = $couriers[($id - 1) % count($couriers)];
                $courierId = $courier['id'];
            } elseif ($type === 'agency_earning') {
                $agency = $agencies[($id - 26) % count($agencies)];
                $agencyId = $agency['id'];
            }

            $paymentDate = $paymentStatus === 'paid'
                ? $expenseDate->copy()->addDays(3 + ($id % 10))->toDateString()
                : null;

            $records[] = [
                'id' => $id,
                'reference' => sprintf('GDR-2026-%06d', $id),
                'expense_type' => $type,
                'source' => $isEarningType ? 'earning' : 'manual',
                'courier_id' => $courierId,
                'agency_id' => $agencyId,
                'earning_id' => $isEarningType ? 500 + $id : null,
                'earning_reference' => $isEarningType
                    ? ($type === 'courier_earning' ? sprintf('HKD-2026-%04d', 100 + $id) : sprintf('AHK-2026-%04d', 100 + $id))
                    : null,
                'current_account_id' => $courierId ? 20 + $courierId : ($agencyId ? 40 + $agencyId : null),
                'current_account_code' => $courierId
                    ? sprintf('CAR-%06d', 20 + $courierId)
                    : ($agencyId ? sprintf('CAR-%06d', 40 + $agencyId) : null),
                'amount' => $amount,
                'vat_rate' => $vatRate,
                'expense_date' => $expenseDate->toDateString(),
                'created_at' => $createdAt->toDateString(),
                'payment_status' => $paymentStatus,
                'payment_date' => $paymentDate,
                'document_no' => sprintf('BLG-2026-%04d', 600 + $id),
                'description' => self::descriptionForType($type, $id),
                'notes' => $id % 6 === 0 ? 'Muhasebe departmanı onayladı.' : null,
            ];

            $id++;
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
        $courier = $row['courier_id']
            ? collect(self::couriers())->firstWhere('id', $row['courier_id'])
            : null;
        $agency = $row['agency_id']
            ? collect(self::agencies())->firstWhere('id', $row['agency_id'])
            : null;

        $vatAmount = round($row['amount'] * ($row['vat_rate'] / 100), 2);
        $grossAmount = round($row['amount'] + $vatAmount, 2);

        $payeeDisplay = $courier['name'] ?? $agency['name'] ?? '—';

        $enriched = array_merge($row, [
            'expense_type_label' => self::expenseTypes()[$row['expense_type']] ?? $row['expense_type'],
            'payment_status_label' => self::paymentStatuses()[$row['payment_status']] ?? $row['payment_status'],
            'source_label' => self::sources()[$row['source']] ?? $row['source'],
            'courier_name' => $courier['name'] ?? null,
            'agency_name' => $agency['name'] ?? null,
            'payee_display' => $payeeDisplay,
            'amount_formatted' => self::formatMoney($row['amount']),
            'vat_amount' => $vatAmount,
            'vat_amount_formatted' => MoneyCalculator::formatVatAmount($vatAmount),
            'gross_amount' => $grossAmount,
            'gross_amount_formatted' => MoneyCalculator::formatIncludingVat($grossAmount),
            'expense_date_formatted' => Carbon::parse($row['expense_date'])->format('d.m.Y'),
            'created_at_formatted' => Carbon::parse($row['created_at'])->format('d.m.Y'),
            'payment_date_formatted' => $row['payment_date']
                ? Carbon::parse($row['payment_date'])->format('d.m.Y')
                : '—',
        ]);

        if ($detailed) {
            $enriched['payment_info'] = [
                'status' => $row['payment_status'],
                'status_label' => $enriched['payment_status_label'],
                'date' => $enriched['payment_date_formatted'],
                'method' => $row['payment_status'] === 'paid' ? 'Banka Havalesi' : '—',
                'reference' => $row['payment_status'] === 'paid'
                    ? 'ODM-2026-'.str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT)
                    : '—',
            ];

            $enriched['current_account_movement'] = $row['current_account_id'] ? [
                'code' => $row['current_account_code'],
                'document_no' => $row['document_no'],
                'date' => $enriched['expense_date_formatted'],
                'type_label' => $row['payment_status'] === 'paid' ? 'Ödeme' : 'Borç Dekontu',
                'debit' => $row['amount'],
                'credit' => $row['payment_status'] === 'paid' ? $row['amount'] : 0,
                'debit_formatted' => self::formatMoney($row['amount']),
                'credit_formatted' => $row['payment_status'] === 'paid' ? self::formatMoney($row['amount']) : '—',
                'description' => 'Gider kaydı: '.$row['reference'],
            ] : null;

            $enriched['documents'] = [
                ['name' => $row['document_no'].'.pdf', 'type' => 'Fatura', 'date' => $enriched['expense_date_formatted']],
                ['name' => 'Ek-'.$row['reference'].'.pdf', 'type' => 'Ek Belge', 'date' => $enriched['created_at_formatted']],
            ];

            $enriched['payee_info'] = $courier ? [
                'type' => 'Kurye',
                'name' => $courier['name'],
                'phone' => collect(CourierDummyData::raw())->firstWhere('id', $row['courier_id'])['phone'] ?? '—',
            ] : ($agency ? [
                'type' => 'Acente',
                'name' => $agency['name'],
                'phone' => collect(AgencyDummyData::all())->firstWhere('id', $row['agency_id'])['phone'] ?? '—',
            ] : [
                'type' => '—',
                'name' => 'İlgili cari hesap yok',
                'phone' => '—',
            ]);
        }

        return $enriched;
    }

    private static function descriptionForType(string $type, int $id): string
    {
        return match ($type) {
            'courier_earning' => 'Kurye hakediş ödemesi — dönem kapanışı',
            'agency_earning' => 'Acente komisyon hakediş ödemesi',
            'personnel' => 'Personel maaş ve yan hak ödemesi',
            'fuel' => 'Araç yakıt gideri — filo operasyonu',
            'office' => 'Ofis kırtasiye ve genel gider',
            'software' => 'Yazılım lisans ve abonelik gideri',
            'advertising' => 'Dijital reklam ve pazarlama gideri',
            'tax' => 'KDV ve stopaj ödemesi',
            'rent' => 'Ofis kira ödemesi',
            default => 'Genel operasyon gideri #'.$id,
        };
    }

    private static function formatMoney(float $amount): string
    {
        return MoneyCalculator::format($amount);
    }
}
