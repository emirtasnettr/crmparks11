<?php

namespace App\Modules\Finance\Data;

use Carbon\Carbon;

class FinanceActivityLogDummyData
{
    private const REFERENCE_DATE = '2026-07-07';

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $recordsCache = null;

    /**
     * Spatie Activity Log uyumlu log_name değerleri.
     *
     * @return array<string, string>
     */
    public static function modules(): array
    {
        return [
            'revenues' => 'Gelirler',
            'expenses' => 'Giderler',
            'collections' => 'Tahsilatlar',
            'payments' => 'Ödemeler',
            'invoices' => 'Faturalar',
            'current_accounts' => 'Cari Hesaplar',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function actionTypes(): array
    {
        return [
            'created' => 'Kayıt Oluşturuldu',
            'updated' => 'Kayıt Güncellendi',
            'deleted' => 'Kayıt Silindi (Soft Delete)',
            'collection_made' => 'Tahsilat Yapıldı',
            'payment_made' => 'Ödeme Yapıldı',
            'invoice_issued' => 'Fatura Kesildi',
            'invoice_cancelled' => 'Fatura İptal Edildi',
            'current_movement_created' => 'Cari Hareketi Oluşturuldu',
            'current_movement_updated' => 'Cari Hareketi Güncellendi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'success' => 'Başarılı',
            'warning' => 'Uyarı',
            'error' => 'Hata',
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
    public static function users(): array
    {
        return [
            ['id' => 1, 'name' => 'Ahmet Yılmaz'],
            ['id' => 2, 'name' => 'Elif Demir'],
            ['id' => 3, 'name' => 'Mehmet Kaya'],
            ['id' => 4, 'name' => 'Zeynep Arslan'],
            ['id' => 5, 'name' => 'Can Öztürk'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function currentAccounts(): array
    {
        return collect(self::records())
            ->pluck('current_account_name')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public static function analyze(array $filters): array
    {
        $filtered = self::filter($filters);
        $reference = Carbon::parse(self::REFERENCE_DATE);

        return [
            'logs' => $filtered,
            'summary' => self::summarize($filtered, $reference),
            'logs_for_modal' => collect($filtered)
                ->mapWithKeys(fn (array $log) => [$log['id'] => self::detailPayload($log)])
                ->all(),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        $reference = Carbon::parse(self::REFERENCE_DATE);

        return collect(self::all())
            ->filter(function (array $log) use ($filters, $reference) {
                if (($filters['action_type'] ?? 'all') !== 'all' && $log['action_type'] !== $filters['action_type']) {
                    return false;
                }

                if (($filters['module'] ?? 'all') !== 'all' && $log['module'] !== $filters['module']) {
                    return false;
                }

                if (($filters['user_id'] ?? 'all') !== 'all' && (int) $log['user_id'] !== (int) $filters['user_id']) {
                    return false;
                }

                if (($filters['current_account'] ?? 'all') !== 'all' && $log['current_account_name'] !== $filters['current_account']) {
                    return false;
                }

                if (! empty($filters['reference'])) {
                    $needle = mb_strtolower($filters['reference']);
                    $haystack = mb_strtolower($log['reference'].' '.$log['description']);
                    if (! str_contains($haystack, $needle)) {
                        return false;
                    }
                }

                $occurred = Carbon::parse($log['occurred_at']);
                $range = $filters['date_range'] ?? 'all';

                if ($range === 'today' && ! $occurred->isSameDay($reference)) {
                    return false;
                }

                if ($range === 'week' && ($occurred->lt($reference->copy()->startOfWeek()) || $occurred->gt($reference->copy()->endOfWeek()))) {
                    return false;
                }

                if ($range === 'month' && ($occurred->month !== $reference->month || $occurred->year !== $reference->year)) {
                    return false;
                }

                if ($range === 'year' && $occurred->year !== $reference->year) {
                    return false;
                }

                return true;
            })
            ->sortByDesc('occurred_at')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, int>
     */
    public static function summarize(array $items, Carbon $reference): array
    {
        $all = collect(self::all());

        return [
            'total' => count($items),
            'today' => $all->filter(fn ($l) => Carbon::parse($l['occurred_at'])->isSameDay($reference))->count(),
            'this_week' => $all->filter(fn ($l) => Carbon::parse($l['occurred_at'])->between(
                $reference->copy()->startOfWeek(),
                $reference->copy()->endOfWeek()
            ))->count(),
            'this_month' => $all->filter(fn ($l) => Carbon::parse($l['occurred_at'])->month === $reference->month
                && Carbon::parse($l['occurred_at'])->year === $reference->year)->count(),
            'critical' => collect($items)->where('is_critical', true)->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return collect(self::records())
            ->map(fn (array $row) => self::enrich($row))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function records(): array
    {
        if (self::$recordsCache !== null) {
            return self::$recordsCache;
        }

        $modules = array_keys(self::modules());
        $actions = array_keys(self::actionTypes());
        $users = self::users();
        $caris = [
            'Burger House Gıda Ltd. Şti.',
            'Napoli Pizza Restoran İşletmeleri A.Ş.',
            'HızlıAl E-Ticaret ve Lojistik A.Ş.',
            'Metro Lojistik Acente A.Ş.',
            'Ahmet Yıldız',
            'Anadolu Kurye Hizmetleri Ltd. Şti.',
            'Yeşil Market Perakende Tic. Ltd. Şti.',
            'CAR-000025 — Burger House',
        ];
        $browsers = [
            ['browser' => 'Chrome 126', 'os' => 'Windows 11'],
            ['browser' => 'Safari 17', 'os' => 'macOS 14'],
            ['browser' => 'Firefox 127', 'os' => 'Ubuntu 24.04'],
            ['browser' => 'Chrome 126', 'os' => 'Android 14'],
            ['browser' => 'Edge 126', 'os' => 'Windows 10'],
        ];
        $ips = ['85.105.42.118', '192.168.1.45', '176.88.12.67', '78.189.55.201', '95.70.33.144'];

        $prefixMap = [
            'revenues' => 'GLR',
            'expenses' => 'GDR',
            'collections' => 'TAH',
            'payments' => 'ODM',
            'invoices' => 'FTR',
            'current_accounts' => 'CAR',
        ];

        $records = [];
        $start = Carbon::parse('2026-03-01 08:15:00');

        for ($id = 1; $id <= 210; $id++) {
            $module = $modules[($id - 1) % count($modules)];
            $action = $actions[($id - 1) % count($actions)];
            $user = $users[($id - 1) % count($users)];
            $cari = $caris[($id - 1) % count($caris)];
            $browser = $browsers[($id - 1) % count($browsers)];
            $occurredAt = $start->copy()->addHours($id * 9 + ($id % 6));

            $status = match (true) {
                $action === 'deleted' || $action === 'invoice_cancelled' => 'warning',
                $id % 47 === 0 => 'error',
                $id % 19 === 0 => 'warning',
                default => 'success',
            };

            $isCritical = in_array($action, ['deleted', 'invoice_cancelled'], true)
                || $status === 'error';

            $subjectId = ($id % 50) + 1;
            $reference = sprintf('%s-2026-%06d', $prefixMap[$module], $subjectId);

            [$oldValues, $newValues] = self::buildChangeSet($module, $action, $reference);

            $records[] = [
                'id' => $id,
                'log_name' => 'finance',
                'module' => $module,
                'action_type' => $action,
                'subject_type' => 'App\\Modules\\Finance\\Models\\'.str($module)->studly()->singular(),
                'subject_id' => $subjectId,
                'reference' => $reference,
                'current_account_name' => $cari,
                'occurred_at' => $occurredAt->toDateTimeString(),
                'user_id' => $user['id'],
                'user_name' => $user['name'],
                'ip_address' => $ips[$id % count($ips)],
                'user_agent' => 'Mozilla/5.0 ('.$browser['os'].') '.$browser['browser'],
                'browser' => $browser['browser'],
                'operating_system' => $browser['os'],
                'status' => $status,
                'is_critical' => $isCritical,
                'description' => self::descriptionFor($module, $action, $reference),
                'properties' => [
                    'old' => $oldValues,
                    'attributes' => $newValues,
                ],
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ];
        }

        self::$recordsCache = $records;

        return self::$recordsCache;
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private static function buildChangeSet(string $module, string $action, string $reference): array
    {
        $old = ['reference' => $reference, 'status' => 'draft', 'amount' => 12500.00];
        $new = ['reference' => $reference, 'status' => 'active', 'amount' => 12500.00];

        if ($action === 'created') {
            $old = [];
            $new = array_merge($new, ['module' => $module, 'created_by' => 'system']);
        }

        if ($action === 'updated' || $action === 'current_movement_updated') {
            $new['amount'] = 13850.00;
            $new['status'] = 'approved';
        }

        if ($action === 'deleted' || $action === 'invoice_cancelled') {
            $old['status'] = 'active';
            $new['status'] = 'cancelled';
            $new['deleted_at'] = Carbon::parse(self::REFERENCE_DATE)->toIso8601String();
        }

        if ($action === 'collection_made' || $action === 'payment_made') {
            $old['paid_amount'] = 0;
            $new['paid_amount'] = 8500.00;
            $new['payment_method'] = 'bank_transfer';
        }

        if ($action === 'invoice_issued') {
            $old['invoice_status'] = 'draft';
            $new['invoice_status'] = 'issued';
            $new['gib_status'] = 'sent';
        }

        if ($action === 'current_movement_created') {
            $old = [];
            $new = [
                'document_no' => $reference,
                'debit' => 0,
                'credit' => 24500.00,
                'type' => 'collection',
            ];
        }

        return [$old, $new];
    }

    private static function descriptionFor(string $module, string $action, string $reference): string
    {
        $moduleLabel = self::modules()[$module] ?? $module;
        $actionLabel = self::actionTypes()[$action] ?? $action;

        return $moduleLabel.' — '.$actionLabel.' ('.$reference.')';
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function enrich(array $row): array
    {
        $occurred = Carbon::parse($row['occurred_at']);

        return array_merge($row, [
            'module_label' => self::modules()[$row['module']] ?? $row['module'],
            'action_type_label' => self::actionTypes()[$row['action_type']] ?? $row['action_type'],
            'status_label' => self::statuses()[$row['status']] ?? $row['status'],
            'date_formatted' => $occurred->format('d.m.Y'),
            'time_formatted' => $occurred->format('H:i:s'),
            'related_route' => self::relatedRoute($row['module'], (int) $row['subject_id']),
            'old_values_json' => json_encode($row['old_values'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'new_values_json' => json_encode($row['new_values'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @param  array<string, mixed>  $log
     * @return array<string, mixed>
     */
    private static function detailPayload(array $log): array
    {
        return [
            'action_type_label' => $log['action_type_label'],
            'module_label' => $log['module_label'],
            'occurred_at' => $log['date_formatted'].' '.$log['time_formatted'],
            'user_name' => $log['user_name'],
            'ip_address' => $log['ip_address'],
            'browser' => $log['browser'],
            'operating_system' => $log['operating_system'],
            'old_values_json' => $log['old_values_json'],
            'new_values_json' => $log['new_values_json'],
            'description' => $log['description'],
            'related_route' => $log['related_route'],
        ];
    }

    private static function relatedRoute(string $module, int $subjectId): ?string
    {
        return match ($module) {
            'revenues' => route('finance.revenues.show', $subjectId),
            'expenses' => route('finance.expenses.show', $subjectId),
            'collections' => route('finance.collections.show', $subjectId),
            'payments' => route('finance.payments.show', $subjectId),
            'invoices' => route('finance.invoices.show', $subjectId),
            'current_accounts' => route('finance.current-accounts.index'),
            default => null,
        };
    }
}
