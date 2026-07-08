<?php

namespace App\Modules\Finance\Data;

use App\Core\Helpers\MoneyCalculator;

use App\Modules\Agency\Data\AgencyDummyData;
use App\Modules\Courier\Data\CourierDummyData;
use Carbon\Carbon;

class FinancePaymentDummyData
{
    private const REFERENCE_DATE = '2026-07-07';

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $recordsCache = null;

    /**
     * @return array<string, string>
     */
    public static function recipientTypes(): array
    {
        return [
            'courier' => 'Kurye',
            'agency' => 'Acente',
            'personnel' => 'Personel',
            'supplier' => 'Tedarikçi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paymentStatuses(): array
    {
        return [
            'paid' => 'Ödendi',
            'partial' => 'Kısmi Ödendi',
            'pending' => 'Bekliyor',
            'cancelled' => 'İptal',
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
     * @return array<int, array{id: int, name: string}>
     */
    public static function couriers(): array
    {
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
     * @return array<int, array{id: int, name: string}>
     */
    public static function personnel(): array
    {
        return [
            ['id' => 1, 'name' => 'Ayşe Yılmaz'],
            ['id' => 2, 'name' => 'Mehmet Demir'],
            ['id' => 3, 'name' => 'Zeynep Kaya'],
            ['id' => 4, 'name' => 'Can Öztürk'],
            ['id' => 5, 'name' => 'Elif Şahin'],
            ['id' => 6, 'name' => 'Burak Aydın'],
            ['id' => 7, 'name' => 'Selin Arslan'],
            ['id' => 8, 'name' => 'Emre Çelik'],
            ['id' => 9, 'name' => 'Deniz Koç'],
            ['id' => 10, 'name' => 'Gizem Polat'],
            ['id' => 11, 'name' => 'Hakan Yıldız'],
            ['id' => 12, 'name' => 'İrem Güneş'],
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function suppliers(): array
    {
        return [
            ['id' => 1, 'name' => 'Atlas Lojistik Ltd. Şti.'],
            ['id' => 2, 'name' => 'Net Yazılım A.Ş.'],
            ['id' => 3, 'name' => 'Ege Yakıt Dağıtım'],
            ['id' => 4, 'name' => 'Merkez Ofis Malzemeleri'],
            ['id' => 5, 'name' => 'Dijital Reklam Ajansı'],
            ['id' => 6, 'name' => 'Güven Sigorta Aracılık'],
            ['id' => 7, 'name' => 'Tekno Bilişim Çözümleri'],
            ['id' => 8, 'name' => 'Anadolu Kırtasiye'],
            ['id' => 9, 'name' => 'Filo Bakım Servisi'],
            ['id' => 10, 'name' => 'Kurumsal Temizlik Hizmetleri'],
        ];
    }

    /**
     * @return array<string, array<int, array{id: int, name: string}>>
     */
    public static function recipientsByType(): array
    {
        return [
            'courier' => self::couriers(),
            'agency' => self::agencies(),
            'personnel' => self::personnel(),
            'supplier' => self::suppliers(),
        ];
    }

    /**
     * @return array<int, array{id: int, reference: string, recipient_type: string}>
     */
    public static function earningOptions(): array
    {
        $options = [];

        foreach (self::records() as $row) {
            if ($row['earning_id'] === null) {
                continue;
            }

            $options[] = [
                'id' => $row['earning_id'],
                'reference' => $row['earning_reference'],
                'recipient_type' => $row['recipient_type'],
                'recipient_id' => $row['recipient_id'],
            ];
        }

        return collect($options)
            ->unique('id')
            ->take(40)
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
            ->sortByDesc('payment_date')
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
            ->filter(function (array $payment) use ($filters, $reference) {
                if (($filters['recipient_type'] ?? 'all') !== 'all' && $payment['recipient_type'] !== $filters['recipient_type']) {
                    return false;
                }

                if (($filters['recipient_id'] ?? 'all') !== 'all') {
                    [$type, $id] = explode(':', $filters['recipient_id'], 2) + [null, null];

                    if ($payment['recipient_type'] !== $type || (int) $payment['recipient_id'] !== (int) $id) {
                        return false;
                    }
                }

                if (($filters['payment_status'] ?? 'all') !== 'all' && $payment['status'] !== $filters['payment_status']) {
                    return false;
                }

                if (($filters['payment_method'] ?? 'all') !== 'all' && $payment['payment_method'] !== $filters['payment_method']) {
                    return false;
                }

                $date = $payment['payment_date']
                    ? Carbon::parse($payment['payment_date'])
                    : Carbon::parse($payment['created_at']);
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
     * @return array<string, float>
     */
    public static function summarize(array $filters): array
    {
        $items = self::filter($filters);
        $reference = Carbon::parse(self::REFERENCE_DATE);

        $activeItems = collect($items)->where('is_active', true);

        $thisMonth = $activeItems->filter(
            fn (array $p) => $p['payment_date']
                && Carbon::parse($p['payment_date'])->month === $reference->month
                && Carbon::parse($p['payment_date'])->year === $reference->year
        );

        $todayPaid = $activeItems->filter(
            fn (array $p) => $p['payment_date'] && Carbon::parse($p['payment_date'])->isSameDay($reference)
        )->sum('paid_amount');

        return [
            'total_payment' => round($activeItems->sum('total_amount'), 2),
            'this_month_payment' => round($thisMonth->sum('paid_amount'), 2),
            'pending_payment' => round($activeItems->whereIn('status', ['pending', 'partial'])->sum('remaining_amount'), 2),
            'today_payment' => round($todayPaid, 2),
            'courier_payment' => round($activeItems->where('recipient_type', 'courier')->sum('paid_amount'), 2),
            'agency_payment' => round($activeItems->where('recipient_type', 'agency')->sum('paid_amount'), 2),
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
        $personnel = self::personnel();
        $suppliers = self::suppliers();
        $methods = array_keys(self::paymentMethods());
        $banks = ['Garanti BBVA', 'İş Bankası', 'Ziraat Bankası', 'Akbank', 'Yapı Kredi'];

        $typePlan = array_merge(
            array_fill(0, 28, 'courier'),
            array_fill(0, 18, 'agency'),
            array_fill(0, 12, 'personnel'),
            array_fill(0, 10, 'supplier'),
            array_fill(0, 7, 'manual'),
        );

        $statusPlan = array_merge(
            array_fill(0, 22, 'paid'),
            array_fill(0, 15, 'partial'),
            array_fill(0, 28, 'pending'),
            array_fill(0, 10, 'cancelled'),
        );

        $records = [];

        foreach ($typePlan as $index => $plannedType) {
            $id = $index + 1;
            $status = $statusPlan[$index];
            $isManual = $plannedType === 'manual';
            $recipientType = $isManual
                ? (['personnel', 'supplier', 'courier'][$id % 3])
                : $plannedType;

            $recipient = match ($recipientType) {
                'courier' => $couriers[($id - 1) % count($couriers)],
                'agency' => $agencies[($id - 1) % count($agencies)],
                'personnel' => $personnel[($id - 1) % count($personnel)],
                default => $suppliers[($id - 1) % count($suppliers)],
            };

            $totalAmount = round(8500 + (($id * 2137) % 185000), 2);
            $method = $methods[$id % count($methods)];
            $hasEarning = ! $isManual && $id % 6 !== 0;
            $earningId = $hasEarning ? 700 + $id : null;
            $earningReference = $hasEarning
                ? match ($recipientType) {
                    'courier' => sprintf('HKD-2026-%04d', 200 + $id),
                    'agency' => sprintf('AHK-2026-%04d', 200 + $id),
                    default => sprintf('HKM-2026-%04d', 200 + $id),
                }
                : null;

            $paymentDate = Carbon::parse('2026-02-01')->addDays($id * 2 + ($id % 5));

            [$paidAmount, $resolvedPaymentDate] = match ($status) {
                'paid' => [$totalAmount, $paymentDate->toDateString()],
                'partial' => [round($totalAmount * (0.4 + ($id % 3) * 0.15), 2), $paymentDate->copy()->subDays(4)->toDateString()],
                'cancelled' => [0, null],
                default => [0, null],
            };

            if ($status === 'pending') {
                $paymentDate = Carbon::parse(self::REFERENCE_DATE)->addDays(2 + ($id % 14));
            }

            $currentAccountOffset = match ($recipientType) {
                'courier' => 20,
                'agency' => 40,
                'personnel' => 60,
                default => 80,
            };

            $records[] = [
                'id' => $id,
                'reference' => sprintf('ODM-2026-%06d', $id),
                'recipient_type' => $recipientType,
                'recipient_id' => $recipient['id'],
                'earning_id' => $earningId,
                'earning_reference' => $earningReference,
                'payment_date' => $resolvedPaymentDate,
                'scheduled_date' => $paymentDate->toDateString(),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'payment_method' => $paidAmount > 0 ? $method : ($status === 'pending' ? $methods[($id + 1) % count($methods)] : null),
                'payment_reference' => $paidAmount > 0 ? 'PAY-2026-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT) : null,
                'bank_account' => $paidAmount > 0 ? $banks[$id % count($banks)].' — TR'.str_pad((string) (1000000000000000 + $id), 16, '0', STR_PAD_LEFT) : null,
                'status' => $status,
                'is_active' => $status !== 'cancelled',
                'source' => $hasEarning ? 'earning' : 'manual',
                'current_account_id' => $currentAccountOffset + $recipient['id'],
                'current_account_code' => sprintf('CAR-%06d', $currentAccountOffset + $recipient['id']),
                'created_at' => $paymentDate->copy()->subDays(8)->toDateString(),
                'description' => self::descriptionFor($recipientType, $isManual),
                'notes' => $id % 7 === 0 ? 'Finans departmanı tarafından onaylandı.' : null,
            ];
        }

        self::$recordsCache = $records;

        return self::$recordsCache;
    }

    private static function descriptionFor(string $recipientType, bool $isManual): string
    {
        if ($isManual) {
            return 'Manuel ödeme kaydı — operasyonel gider kapatma';
        }

        return match ($recipientType) {
            'courier' => 'Kurye hakediş ödemesi — dönem kapanışı',
            'agency' => 'Acente hakediş ödemesi — komisyon ödemesi',
            'personnel' => 'Personel maaş ve yan hak ödemesi',
            default => 'Tedarikçi fatura ödemesi',
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function enrich(array $row, bool $detailed = false): array
    {
        $recipient = collect(self::recipientsByType()[$row['recipient_type']] ?? [])
            ->firstWhere('id', $row['recipient_id']);

        $remaining = round($row['total_amount'] - $row['paid_amount'], 2);

        $enriched = array_merge($row, [
            'recipient_name' => $recipient['name'] ?? '—',
            'recipient_type_label' => self::recipientTypes()[$row['recipient_type']] ?? $row['recipient_type'],
            'status_label' => self::paymentStatuses()[$row['status']] ?? $row['status'],
            'payment_method_label' => $row['payment_method']
                ? (self::paymentMethods()[$row['payment_method']] ?? $row['payment_method'])
                : '—',
            'source_label' => $row['source'] === 'earning' ? 'Hakediş' : 'Manuel',
            'total_amount_formatted' => self::formatMoney($row['total_amount']),
            'paid_amount_formatted' => self::formatMoney($row['paid_amount']),
            'remaining_amount' => $remaining,
            'remaining_amount_formatted' => self::formatMoney($remaining),
            'payment_date_formatted' => $row['payment_date']
                ? Carbon::parse($row['payment_date'])->format('d.m.Y')
                : '—',
            'scheduled_date_formatted' => Carbon::parse($row['scheduled_date'])->format('d.m.Y'),
            'created_at_formatted' => Carbon::parse($row['created_at'])->format('d.m.Y'),
            'earning_reference_display' => $row['earning_reference'] ?? '—',
            'recipient_filter_key' => $row['recipient_type'].':'.$row['recipient_id'],
        ]);

        if ($detailed) {
            $enriched['recipient_info'] = self::buildRecipientInfo($row, $recipient);
            $enriched['earning_info'] = $row['earning_id'] ? [
                'id' => $row['earning_id'],
                'reference' => $row['earning_reference'],
                'amount_formatted' => $enriched['total_amount_formatted'],
                'type_label' => $enriched['recipient_type_label'].' Hakedişi',
            ] : null;
            $enriched['payment_info'] = [
                'method' => $enriched['payment_method_label'],
                'reference' => $row['payment_reference'] ?? '—',
                'bank_account' => $row['bank_account'] ?? '—',
                'date' => $enriched['payment_date_formatted'],
                'status' => $enriched['status_label'],
            ];
            $enriched['payment_history'] = self::buildPaymentHistory($row, $remaining);
            $enriched['current_account_movement'] = $row['paid_amount'] > 0 ? [
                'code' => $row['current_account_code'],
                'document_no' => $row['payment_reference'] ?? $row['reference'],
                'date' => $enriched['payment_date_formatted'],
                'type_label' => 'Ödeme',
                'debit' => $row['paid_amount'],
                'credit' => 0,
                'debit_formatted' => self::formatMoney($row['paid_amount']),
                'credit_formatted' => '—',
                'description' => 'Ödeme: '.$row['reference'],
            ] : null;
            $enriched['receipts'] = self::buildReceipts($row);
        }

        return $enriched;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{id: int, name: string}|null  $recipient
     * @return array<string, string>
     */
    private static function buildRecipientInfo(array $row, ?array $recipient): array
    {
        $phone = '—';

        if ($row['recipient_type'] === 'courier') {
            $courier = collect(CourierDummyData::raw())->firstWhere('id', $row['recipient_id']);
            $phone = $courier['phone'] ?? '—';
        } elseif ($row['recipient_type'] === 'agency') {
            $agency = collect(AgencyDummyData::all())->firstWhere('id', $row['recipient_id']);
            $phone = $agency['phone'] ?? '—';
        }

        return [
            'type' => self::recipientTypes()[$row['recipient_type']] ?? '—',
            'name' => $recipient['name'] ?? '—',
            'code' => $row['current_account_code'],
            'phone' => $phone,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array<string, mixed>>
     */
    private static function buildPaymentHistory(array $row, float $remaining): array
    {
        if ($row['paid_amount'] <= 0) {
            return [];
        }

        if ($row['status'] === 'partial') {
            $first = round($row['paid_amount'] * 0.6, 2);
            $second = round($row['paid_amount'] - $first, 2);

            return [
                self::historyEntry(1, $row, $first, Carbon::parse($row['payment_date'])->subDays(10)->toDateString()),
                self::historyEntry(2, $row, $second, $row['payment_date']),
            ];
        }

        return [self::historyEntry(1, $row, $row['paid_amount'], $row['payment_date'])];
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
            'reference' => ($row['payment_reference'] ?? 'PAY').'-'.$seq,
            'bank' => $row['bank_account'] ? explode(' — ', $row['bank_account'])[0] : '—',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array<string, string>>
     */
    private static function buildReceipts(array $row): array
    {
        if ($row['paid_amount'] <= 0) {
            return [];
        }

        $items = [
            ['name' => 'Dekont-'.$row['reference'].'.pdf', 'type' => 'Banka Dekontu', 'date' => Carbon::parse($row['payment_date'] ?? $row['created_at'])->format('d.m.Y')],
        ];

        if ($row['status'] === 'partial') {
            $items[] = ['name' => 'Dekont-'.$row['reference'].'-2.pdf', 'type' => 'Kısmi Ödeme', 'date' => Carbon::parse($row['payment_date'])->format('d.m.Y')];
        }

        return $items;
    }

    private static function formatMoney(float $amount): string
    {
        return MoneyCalculator::format($amount);
    }
}
