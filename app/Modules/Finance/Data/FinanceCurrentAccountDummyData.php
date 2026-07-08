<?php

namespace App\Modules\Finance\Data;

use App\Support\DemoData;
use App\Core\Helpers\MoneyCalculator;

use App\Modules\Agency\Data\AgencyDummyData;
use App\Modules\Business\Data\BusinessDummyData;
use App\Modules\Courier\Data\CourierDummyData;
use Carbon\Carbon;

class FinanceCurrentAccountDummyData
{
    private const REFERENCE_DATE = '2026-07-07';

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $accountsCache = null;

    /** @var array<int, array<int, array<string, mixed>>>|null */
    private static ?array $movementsCache = null;

    /**
     * @return array<string, string>
     */
    public static function accountTypes(): array
    {
        return [
            'business' => 'İşletme',
            'courier' => 'Kurye',
            'agency' => 'Acente',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'passive' => 'Pasif',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function balanceStatuses(): array
    {
        return [
            'receivable' => 'Alacaklı',
            'payable' => 'Borçlu',
            'zero' => 'Sıfır',
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
            'debit_note' => 'Borç Dekontu',
            'credit_note' => 'Alacak Dekontu',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(self::accounts())
            ->map(fn (array $account) => self::enrichAccount($account))
            ->sortBy('code')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        $search = mb_strtolower(trim($filters['search'] ?? ''));

        return collect(self::all())
            ->filter(function (array $account) use ($filters, $search) {
                if (($filters['type'] ?? 'all') !== 'all' && $account['type'] !== $filters['type']) {
                    return false;
                }

                if (($filters['status'] ?? 'all') !== 'all' && $account['status'] !== $filters['status']) {
                    return false;
                }

                if (($filters['balance_status'] ?? 'all') !== 'all' && $account['balance_status'] !== $filters['balance_status']) {
                    return false;
                }

                if ($search !== '') {
                    $haystack = mb_strtolower($account['code'].' '.$account['title'].' '.$account['phone']);

                    if (! str_contains($haystack, $search)) {
                        return false;
                    }
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
        $accounts = self::filter($filters);

        $totalReceivable = collect($accounts)->where('balance', '>', 0)->sum('balance');
        $totalPayable = abs(collect($accounts)->where('balance', '<', 0)->sum('balance'));
        $overdueReceivable = collect($accounts)->sum('overdue_receivable');
        $overduePayable = collect($accounts)->sum('overdue_payable');

        return [
            'count' => count($accounts),
            'total_receivable' => round($totalReceivable, 2),
            'total_payable' => round($totalPayable, 2),
            'net_balance' => round(collect($accounts)->sum('balance'), 2),
            'overdue_receivable' => round($overdueReceivable, 2),
            'overdue_payable' => round($overduePayable, 2),
        ];
    }

    public static function find(int $id): ?array
    {
        $account = collect(self::accounts())->firstWhere('id', $id);

        return $account ? self::enrichAccount($account) : null;
    }

    /**
     * @return array<int, array{id: int, code: string, title: string, type: string}>
     */
    public static function options(): array
    {
        return collect(self::all())
            ->map(fn (array $account) => [
                'id' => $account['id'],
                'code' => $account['code'],
                'title' => $account['title'],
                'type' => $account['type'],
                'type_label' => $account['type_label'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function accounts(): array
    {
        if (self::$accountsCache !== null) {
            return self::$accountsCache;
        }

        $accounts = [];
        $id = 1;

        foreach (self::businessSeeds() as $index => $seed) {
            $accounts[] = self::baseAccount($id++, 'business', $seed, $index);
        }

        foreach (self::courierSeeds() as $index => $seed) {
            $accounts[] = self::baseAccount($id++, 'courier', $seed, $index);
        }

        foreach (self::agencySeeds() as $index => $seed) {
            $accounts[] = self::baseAccount($id++, 'agency', $seed, $index);
        }

        self::$accountsCache = $accounts;

        return self::$accountsCache;
    }

    /**
     * @param  array<string, mixed>  $seed
     * @return array<string, mixed>
     */
    private static function baseAccount(int $id, string $type, array $seed, int $index): array
    {
        return [
            'id' => $id,
            'code' => sprintf('CAR-%06d', $id),
            'type' => $type,
            'entity_type' => $type,
            'entity_id' => $seed['entity_id'] ?? null,
            'title' => $seed['title'],
            'phone' => $seed['phone'],
            'email' => $seed['email'] ?? null,
            'city' => $seed['city'] ?? 'İstanbul',
            'tax_number' => $seed['tax_number'] ?? null,
            'status' => $index % 9 === 0 ? 'passive' : 'active',
            'address' => $seed['address'] ?? null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function businessSeeds(): array
    {
        $businesses = BusinessDummyData::all();
        $extra = [
            ['title' => 'Tatlı Diyarı Pastane ve Unlu Mamuller', 'phone' => '0216 444 11 22', 'city' => 'İstanbul'],
            ['title' => 'Et ve Et Ürünleri Kasaplık Tic. Ltd. Şti.', 'phone' => '0312 555 66 77', 'city' => 'Ankara'],
            ['title' => 'Taze Manav ve Sebze Meyve Tic.', 'phone' => '0232 333 22 11', 'city' => 'İzmir'],
            ['title' => 'Lezzet Durağı Restoran İşletmeleri', 'phone' => '0212 777 88 99', 'city' => 'İstanbul'],
            ['title' => 'Organik Yaşam Market Zinciri A.Ş.', 'phone' => '0242 111 22 33', 'city' => 'Antalya'],
            ['title' => 'Şehir İçi Hızlı Teslimat Gıda Ltd.', 'phone' => '0224 444 55 66', 'city' => 'Bursa'],
            ['title' => 'Gurme Catering Hizmetleri A.Ş.', 'phone' => '0216 888 99 00', 'city' => 'İstanbul'],
            ['title' => 'Akşam Yemeği Paket Servis Ltd. Şti.', 'phone' => '0312 222 33 44', 'city' => 'Ankara'],
            ['title' => 'Deniz Mahsulleri Balık Restoran', 'phone' => '0232 555 44 33', 'city' => 'İzmir'],
            ['title' => 'Ev Yemekleri Mutfağı İşletmesi', 'phone' => '0212 666 77 88', 'city' => 'İstanbul'],
            ['title' => 'Premium Steakhouse İşletmeleri', 'phone' => '0216 999 00 11', 'city' => 'İstanbul'],
            ['title' => 'Vegan Life Beslenme Ltd. Şti.', 'phone' => '0312 888 77 66', 'city' => 'Ankara'],
        ];

        $seeds = [];

        foreach ($businesses as $business) {
            $seeds[] = [
                'entity_id' => $business['id'],
                'title' => $business['company_name'],
                'phone' => $business['phone'],
                'city' => $business['city'],
                'tax_number' => '1'.str_pad((string) $business['id'], 9, '0', STR_PAD_LEFT),
            ];
        }

        foreach ($extra as $index => $item) {
            $seeds[] = array_merge($item, [
                'entity_id' => 100 + $index,
                'email' => 'muhasebe@'.str($item['title'])->slug()->limit(20, '')->toString().'.com.tr',
                'tax_number' => '2'.str_pad((string) (100 + $index), 9, '0', STR_PAD_LEFT),
            ]);
        }

        return array_slice($seeds, 0, 20);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function courierSeeds(): array
    {
        return collect(CourierDummyData::raw())
            ->take(20)
            ->map(fn (array $courier) => [
                'entity_id' => $courier['id'],
                'title' => trim($courier['first_name'].' '.$courier['last_name']),
                'phone' => $courier['phone'],
                'city' => $courier['city'] ?? 'İstanbul',
                'email' => $courier['email'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function agencySeeds(): array
    {
        return collect(AgencyDummyData::all())
            ->take(10)
            ->map(fn (array $agency) => [
                'entity_id' => $agency['id'],
                'title' => $agency['company_name'],
                'phone' => $agency['phone'],
                'city' => $agency['city'],
                'email' => $agency['email'] ?? null,
                'tax_number' => $agency['tax_number'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $account
     * @return array<string, mixed>
     */
    private static function enrichAccount(array $account): array
    {
        $movements = self::movementsForAccount($account['id'], $account['type']);
        $totals = self::calculateTotals($movements);
        $balance = round($totals['debit'] - $totals['credit'], 2);
        $balanceStatus = self::resolveBalanceStatus($balance);
        $lastMovement = collect($movements)->sortByDesc('date')->first();
        $lastInvoice = collect($movements)->first(fn ($m) => in_array($m['type'], ['invoice', 'debit_note'], true));
        $lastEarning = collect($movements)->first(fn ($m) => $m['type'] === 'earning');
        $referenceDate = Carbon::parse(self::REFERENCE_DATE);

        $overdueReceivable = $account['type'] === 'business' && $balance > 0
            ? round(min($balance, 15000 + ($account['id'] % 7) * 4200), 2)
            : 0;

        $overduePayable = in_array($account['type'], ['courier', 'agency'], true) && $balance < 0
            ? round(min(abs($balance), 8000 + ($account['id'] % 5) * 3100), 2)
            : 0;

        return array_merge($account, [
            'type_label' => self::accountTypes()[$account['type']],
            'status_label' => self::statuses()[$account['status']],
            'total_debit' => $totals['debit'],
            'total_credit' => $totals['credit'],
            'total_debit_formatted' => self::formatMoney($totals['debit']),
            'total_credit_formatted' => self::formatMoney($totals['credit']),
            'balance' => $balance,
            'balance_formatted' => self::formatMoney($balance),
            'balance_status' => $balanceStatus,
            'balance_status_label' => self::balanceStatuses()[$balanceStatus],
            'balance_tone' => match ($balanceStatus) {
                'receivable' => 'positive',
                'payable' => 'negative',
                default => 'zero',
            },
            'last_movement_at' => $lastMovement['date'] ?? null,
            'last_movement_formatted' => $lastMovement ? Carbon::parse($lastMovement['date'])->format('d.m.Y') : '—',
            'last_movement_label' => $lastMovement['type_label'] ?? '—',
            'overdue_receivable' => $overdueReceivable,
            'overdue_payable' => $overduePayable,
            'overdue_receivable_formatted' => self::formatMoney($overdueReceivable),
            'overdue_payable_formatted' => self::formatMoney($overduePayable),
            'last_invoice' => $lastInvoice ? [
                'document_no' => $lastInvoice['document_no'],
                'date' => Carbon::parse($lastInvoice['date'])->format('d.m.Y'),
                'amount' => $lastInvoice['debit'],
                'amount_formatted' => self::formatMoney($lastInvoice['debit']),
            ] : null,
            'last_earning' => $lastEarning ? [
                'document_no' => $lastEarning['document_no'],
                'date' => Carbon::parse($lastEarning['date'])->format('d.m.Y'),
                'amount' => $lastEarning['credit'],
                'amount_formatted' => self::formatMoney($lastEarning['credit']),
            ] : null,
            'movements' => self::enrichMovements($movements),
            'recent_movements' => array_slice(self::enrichMovements($movements), 0, 5),
            'days_since_last_movement' => $lastMovement
                ? (int) Carbon::parse($lastMovement['date'])->diffInDays($referenceDate)
                : null,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function movementsForAccount(int $accountId, string $type): array
    {
        if (self::$movementsCache === null) {
            self::$movementsCache = [];
            foreach (self::accounts() as $account) {
                self::$movementsCache[$account['id']] = self::generateMovements($account['id'], $account['type']);
            }
        }

        return self::$movementsCache[$accountId] ?? self::generateMovements($accountId, $type);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function generateMovements(int $accountId, string $type): array
    {
        $count = 10 + ($accountId % 6);
        $movements = [];
        $start = Carbon::parse('2026-01-05')->addDays($accountId % 20);

        for ($i = 0; $i < $count; $i++) {
            $date = $start->copy()->addDays($i * 9 + ($accountId % 4));
            $amount = round(3500 + (($accountId * 17 + $i * 131) % 48000), 2);
            $sequence = $accountId * 100 + $i + 1;

            if ($type === 'business') {
                $isInvoice = $i % 3 !== 2;
                $movements[] = $isInvoice
                    ? [
                        'id' => $sequence,
                        'date' => $date->toDateString(),
                        'document_no' => sprintf('FTR-2026-%04d', 700 + $sequence),
                        'type' => $i % 5 === 4 ? 'debit_note' : 'invoice',
                        'type_label' => $i % 5 === 4 ? 'Borç Dekontu' : 'Fatura',
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => $i % 5 === 4 ? 'Düzeltme borç dekontu' : 'Aylık hizmet faturası',
                        'related_type' => 'invoice',
                        'related_id' => $sequence,
                    ]
                    : [
                        'id' => $sequence,
                        'date' => $date->toDateString(),
                        'document_no' => sprintf('THS-2026-%04d', 300 + $sequence),
                        'type' => $i % 4 === 3 ? 'credit_note' : 'collection',
                        'type_label' => $i % 4 === 3 ? 'Alacak Dekontu' : 'Tahsilat',
                        'debit' => 0,
                        'credit' => round($amount * 0.85, 2),
                        'description' => $i % 4 === 3 ? 'İade alacak dekontu' : 'Banka havalesi tahsilat',
                        'related_type' => 'bank',
                        'related_id' => $sequence,
                    ];
            } else {
                $isEarning = $i % 3 !== 1;
                $movements[] = $isEarning
                    ? [
                        'id' => $sequence,
                        'date' => $date->toDateString(),
                        'document_no' => sprintf('%s-2026-%03d', $type === 'courier' ? 'HKD' : 'AHK', 10 + ($sequence % 90)),
                        'type' => $i % 6 === 5 ? 'credit_note' : 'earning',
                        'type_label' => $i % 6 === 5 ? 'Alacak Dekontu' : 'Hakediş',
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => $type === 'courier' ? 'Haftalık kurye hakedişi' : 'Aylık acente komisyon hakedişi',
                        'related_type' => 'earning',
                        'related_id' => $sequence,
                    ]
                    : [
                        'id' => $sequence,
                        'date' => $date->toDateString(),
                        'document_no' => sprintf('ODM-2026-%04d', 500 + $sequence),
                        'type' => $i % 5 === 2 ? 'debit_note' : 'payment',
                        'type_label' => $i % 5 === 2 ? 'Borç Dekontu' : 'Ödeme',
                        'debit' => round($amount * 0.9, 2),
                        'credit' => 0,
                        'description' => $i % 5 === 2 ? 'Mahsup borç dekontu' : 'Banka ödeme talimatı',
                        'related_type' => 'bank',
                        'related_id' => $sequence,
                    ];
            }
        }

        return collect($movements)->sortBy('date')->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $movements
     * @return array{debit: float, credit: float}
     */
    private static function calculateTotals(array $movements): array
    {
        return [
            'debit' => round(collect($movements)->sum('debit'), 2),
            'credit' => round(collect($movements)->sum('credit'), 2),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $movements
     * @return array<int, array<string, mixed>>
     */
    private static function enrichMovements(array $movements): array
    {
        $running = 0;

        return collect($movements)
            ->sortBy('date')
            ->values()
            ->map(function (array $movement) use (&$running) {
                $running = round($running + $movement['debit'] - $movement['credit'], 2);

                return array_merge($movement, [
                    'date_formatted' => Carbon::parse($movement['date'])->format('d.m.Y'),
                    'debit_formatted' => $movement['debit'] > 0 ? self::formatMoney($movement['debit']) : '—',
                    'credit_formatted' => $movement['credit'] > 0 ? self::formatMoney($movement['credit']) : '—',
                    'balance' => $running,
                    'balance_formatted' => self::formatMoney($running),
                ]);
            })
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    private static function resolveBalanceStatus(float $balance): string
    {
        if ($balance > 0) {
            return 'receivable';
        }

        if ($balance < 0) {
            return 'payable';
        }

        return 'zero';
    }

    private static function formatMoney(float $amount): string
    {
        return MoneyCalculator::format($amount);
    }
}
