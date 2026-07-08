<?php

namespace App\Modules\Finance\Data;

use App\Core\Helpers\MoneyCalculator;
use App\Support\DemoData;
use Carbon\Carbon;

class FinanceDashboardDummyData
{
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

    public static function referenceDate(): Carbon
    {
        return Carbon::parse('2026-07-07');
    }

    /**
     * @return array<string, mixed>
     */
    public static function dashboard(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array
    {
        return [
            'period' => $period,
            'periods' => self::periods(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'kpis' => self::kpis($period),
            'charts' => self::charts(),
            'recent_transactions' => self::recentTransactions(),
            'pending_collections' => self::pendingCollections(),
            'pending_payments' => self::pendingPayments(),
            'today_summary' => self::todaySummary(),
        ];
    }

    /**
     * @return array<string, string|float|int>
     */
    public static function kpis(string $period): array
    {
        if (! DemoData::enabled()) {
            return [
                'total_revenue' => 0.0,
                'total_revenue_formatted' => self::formatMoney(0),
                'total_expense' => 0.0,
                'total_expense_formatted' => self::formatMoney(0),
                'net_profit' => 0.0,
                'net_profit_formatted' => self::formatMoney(0),
                'profit_margin' => 0.0,
                'profit_margin_formatted' => '0,0%',
                'pending_collection' => 0.0,
                'pending_collection_formatted' => self::formatMoney(0),
                'pending_payment' => 0.0,
                'pending_payment_formatted' => self::formatMoney(0),
                'monthly_earnings_count' => 0,
                'active_accounts' => 0,
            ];
        }

        $scale = match ($period) {
            'today' => 0.034,
            'week' => 0.23,
            'year' => 11.8,
            default => 1.0,
        };

        $revenue = round(8_450_000 * $scale, 2);
        $expense = round(6_120_000 * $scale, 2);
        $profit = round($revenue - $expense, 2);
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;

        return [
            'total_revenue' => $revenue,
            'total_revenue_formatted' => self::formatMoney($revenue),
            'total_expense' => $expense,
            'total_expense_formatted' => self::formatMoney($expense),
            'net_profit' => $profit,
            'net_profit_formatted' => self::formatMoney($profit),
            'profit_margin' => $margin,
            'profit_margin_formatted' => number_format($margin, 1, ',', '.').'%',
            'pending_collection' => round(1_245_000 * min($scale, 1), 2),
            'pending_collection_formatted' => self::formatMoney(round(1_245_000 * min($scale, 1), 2)),
            'pending_payment' => round(892_500 * min($scale, 1), 2),
            'pending_payment_formatted' => self::formatMoney(round(892_500 * min($scale, 1), 2)),
            'monthly_earnings_count' => (int) max(1, round(156 * min($scale, 1))),
            'active_accounts' => 342,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function charts(): array
    {
        if (! DemoData::enabled()) {
            return [
                'months' => [],
                'revenue_expense' => ['revenue' => [], 'expense' => []],
                'profit' => [],
                'revenue_by_business' => [],
                'expense_breakdown' => [],
            ];
        }

        $months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
        $revenue = [6200000, 6850000, 7100000, 6980000, 7450000, 8120000, 8450000, 0, 0, 0, 0, 0];
        $expense = [4800000, 5100000, 5280000, 5150000, 5420000, 5890000, 6120000, 0, 0, 0, 0, 0];
        $profit = array_map(fn ($r, $e) => $r - $e, $revenue, $expense);

        // Projeksiyon (Ağu-Aralık)
        for ($i = 7; $i < 12; $i++) {
            $revenue[$i] = (int) round($revenue[6] * (1 + ($i - 6) * 0.02));
            $expense[$i] = (int) round($expense[6] * (1 + ($i - 6) * 0.015));
            $profit[$i] = $revenue[$i] - $expense[$i];
        }

        return [
            'months' => $months,
            'revenue_expense' => [
                'revenue' => $revenue,
                'expense' => $expense,
            ],
            'profit' => $profit,
            'revenue_by_business' => [
                ['label' => 'Burger House', 'value' => 1845000],
                ['label' => 'HızlıAl E-Ticaret', 'value' => 1620000],
                ['label' => 'Napoli Pizza', 'value' => 1285000],
                ['label' => 'Metro Lojistik Acente', 'value' => 980000],
                ['label' => 'Yeşil Market', 'value' => 845000],
                ['label' => 'Diğer', 'value' => 1875000],
            ],
            'expense_breakdown' => [
                ['label' => 'Kurye', 'value' => 3420000],
                ['label' => 'Acente', 'value' => 1850000],
                ['label' => 'Diğer', 'value' => 850000],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function recentTransactions(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

        $rows = [
            ['2026-07-07 16:42', 'Tahsilat', 'Burger House Gıda Ltd. Şti.', 125000, 'completed'],
            ['2026-07-07 15:18', 'Kurye Ödemesi', 'Ahmet Yıldız', -47800, 'completed'],
            ['2026-07-07 14:05', 'Acente Hakediş', 'Metro Lojistik Acente A.Ş.', -48500, 'pending'],
            ['2026-07-07 11:30', 'Fatura Kesimi', 'Napoli Pizza Restoran A.Ş.', 89200, 'completed'],
            ['2026-07-07 10:15', 'Tahsilat', 'HızlıAl E-Ticaret A.Ş.', 245000, 'completed'],
            ['2026-07-06 17:55', 'Kurye Ödemesi', 'Murat Kaya', -35280, 'completed'],
            ['2026-07-06 16:20', 'Gider', 'Operasyon — Yakıt', -12450, 'completed'],
            ['2026-07-06 14:08', 'Tahsilat', 'Yeşil Market Ltd. Şti.', 67800, 'overdue'],
            ['2026-07-06 11:45', 'Acente Hakediş', 'Hızlı Kurye Acentesi', -62700, 'pending'],
            ['2026-07-05 18:30', 'Fatura Kesimi', 'Tatlı Diyarı Pastane', 45600, 'completed'],
            ['2026-07-05 15:22', 'Kurye Ödemesi', 'Emre Demir', -26640, 'completed'],
            ['2026-07-05 13:10', 'Tahsilat', 'Et ve Et Ürünleri Kasaplık', 98400, 'completed'],
            ['2026-07-04 17:40', 'Gider', 'Sigorta Primi', -28500, 'completed'],
            ['2026-07-04 10:05', 'Tahsilat', 'Kahve Durağı Ltd. Şti.', 52300, 'pending'],
            ['2026-07-03 16:18', 'Acente Hakediş', 'Anadolu Kurye Hizmetleri', -142500, 'approval'],
        ];

        return collect($rows)->map(function (array $row, int $index) {
            $occurredAt = Carbon::parse($row[0]);

            return [
                'id' => $index + 1,
                'occurred_at' => $occurredAt->format('d.m.Y'),
                'occurred_at_time' => $occurredAt->format('H:i'),
                'type' => $row[1],
                'account' => $row[2],
                'amount' => $row[3],
                'amount_formatted' => ($row[3] < 0 ? '−' : '').self::formatMoney(abs($row[3])),
                'is_negative' => $row[3] < 0,
                'status' => $row[4],
                'status_label' => self::transactionStatusLabel($row[4]),
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function pendingCollections(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

        return [
            ['business' => 'Yeşil Market Perakende Tic. Ltd. Şti.', 'invoice' => 'FTR-2026-0842', 'due_date' => '2026-06-28', 'amount' => 67800],
            ['business' => 'Kahve Durağı İşletmecilik Ltd. Şti.', 'invoice' => 'FTR-2026-0856', 'due_date' => '2026-07-03', 'amount' => 52300],
            ['business' => 'Tatlı Diyarı Pastane', 'invoice' => 'FTR-2026-0861', 'due_date' => '2026-07-05', 'amount' => 38900],
            ['business' => 'Taze Manav ve Sebze Meyve Tic.', 'invoice' => 'FTR-2026-0870', 'due_date' => '2026-07-10', 'amount' => 112400],
            ['business' => 'Burger House Gıda Ltd. Şti.', 'invoice' => 'FTR-2026-0875', 'due_date' => '2026-07-12', 'amount' => 245600],
            ['business' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.', 'invoice' => 'FTR-2026-0882', 'due_date' => '2026-07-15', 'amount' => 428000],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function pendingPayments(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

        return [
            ['payee' => 'Metro Lojistik Acente A.Ş.', 'type' => 'Acente', 'reference' => 'AHK-2026-003', 'payment_date' => '2026-07-10', 'amount' => 36800, 'status' => 'pending'],
            ['payee' => 'Ahmet Yıldız', 'type' => 'Kurye', 'reference' => 'HKD-2026-014', 'payment_date' => '2026-07-08', 'amount' => 47800, 'status' => 'pending'],
            ['payee' => 'Anadolu Kurye Hizmetleri Ltd.', 'type' => 'Acente', 'reference' => 'AHK-2026-004', 'payment_date' => '2026-07-12', 'amount' => 142500, 'status' => 'approval'],
            ['payee' => 'Emre Demir', 'type' => 'Kurye', 'reference' => 'HKD-2026-019', 'payment_date' => '2026-07-09', 'amount' => 20720, 'status' => 'pending'],
            ['payee' => 'Hızlı Kurye Acentesi Ltd. Şti.', 'type' => 'Acente', 'reference' => 'AHK-2026-014', 'payment_date' => '2026-07-11', 'amount' => 31000, 'status' => 'partial'],
            ['payee' => 'Burak Şen', 'type' => 'Kurye', 'reference' => 'HKD-2026-006', 'payment_date' => '2026-07-07', 'amount' => 25640, 'status' => 'overdue'],
        ];
    }

    /**
     * @return array<string, string|int|float>
     */
    public static function todaySummary(): array
    {
        if (! DemoData::enabled()) {
            return [
                'revenue' => 0,
                'revenue_formatted' => self::formatMoney(0),
                'expense' => 0,
                'expense_formatted' => self::formatMoney(0),
                'profit' => 0,
                'profit_formatted' => self::formatMoney(0),
                'new_earnings' => 0,
                'new_invoices' => 0,
                'pending_approvals' => 0,
            ];
        }

        return [
            'revenue' => 460200,
            'revenue_formatted' => self::formatMoney(460200),
            'expense' => 108750,
            'expense_formatted' => self::formatMoney(108750),
            'profit' => 351450,
            'profit_formatted' => self::formatMoney(351450),
            'new_earnings' => 12,
            'new_invoices' => 8,
            'pending_approvals' => 5,
        ];
    }

    public static function enrichPendingCollections(array $items): array
    {
        $today = self::referenceDate();

        return collect($items)->map(function (array $item) use ($today) {
            $due = Carbon::parse($item['due_date']);
            $delay = (int) $today->diffInDays($due, false);

            return array_merge($item, [
                'due_date_formatted' => $due->format('d.m.Y'),
                'amount_formatted' => self::formatMoney($item['amount']),
                'delay_days' => abs($delay),
                'is_overdue' => $delay < 0,
                'delay_label' => $delay < 0 ? abs($delay).' gün gecikmiş' : ($delay === 0 ? 'Bugün' : $delay.' gün kaldı'),
            ]);
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function enrichPendingPayments(array $items): array
    {
        return collect($items)->map(function (array $item) {
            return array_merge($item, [
                'payment_date_formatted' => Carbon::parse($item['payment_date'])->format('d.m.Y'),
                'amount_formatted' => self::formatMoney($item['amount']),
                'status_label' => self::paymentStatusLabel($item['status']),
            ]);
        })->all();
    }

    private static function formatMoney(float $amount): string
    {
        return MoneyCalculator::format($amount);
    }

    private static function transactionStatusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Tamamlandı',
            'pending' => 'Bekliyor',
            'overdue' => 'Gecikmiş',
            'approval' => 'Onay Bekliyor',
            default => $status,
        };
    }

    private static function paymentStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Bekliyor',
            'approval' => 'Onay Bekliyor',
            'partial' => 'Kısmi Ödendi',
            'overdue' => 'Gecikmiş',
            'paid' => 'Ödendi',
            default => $status,
        };
    }
}
