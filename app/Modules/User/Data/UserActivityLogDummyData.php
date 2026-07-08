<?php

namespace App\Modules\User\Data;

use App\Support\DemoData;
use Carbon\Carbon;

class UserActivityLogDummyData
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
            'auth' => 'Kimlik Doğrulama',
            'users' => 'Kullanıcılar',
            'roles' => 'Roller',
            'permissions' => 'Yetkiler',
            'businesses' => 'İşletmeler',
            'couriers' => 'Kuryeler',
            'agencies' => 'Acenteler',
            'contracts' => 'Sözleşmeler',
            'earnings' => 'Hakedişler',
            'collections' => 'Tahsilatlar',
            'payments' => 'Ödemeler',
            'invoices' => 'Faturalar',
            'finance' => 'Finans',
            'reports' => 'Raporlar',
            'settings' => 'Sistem Ayarları',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function activityTypes(): array
    {
        return [
            'login' => 'Giriş Yapıldı',
            'logout' => 'Çıkış Yapıldı',
            'login_failed' => 'Başarısız Giriş',
            'user_created' => 'Kullanıcı Oluşturuldu',
            'user_updated' => 'Kullanıcı Güncellendi',
            'role_changed' => 'Rol Değiştirildi',
            'permission_updated' => 'Yetki Güncellendi',
            'password_changed' => 'Şifre Değiştirildi',
            'business_created' => 'İşletme Oluşturuldu',
            'courier_updated' => 'Kurye Güncellendi',
            'earning_created' => 'Hakediş Oluşturuldu',
            'collection_made' => 'Tahsilat Yapıldı',
            'payment_made' => 'Ödeme Yapıldı',
            'invoice_issued' => 'Fatura Kesildi',
            'settings_updated' => 'Ayarlar Güncellendi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function roles(): array
    {
        return UserManagementDummyData::roles();
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'success' => 'Başarılı',
            'warning' => 'Uyarı',
            'failed' => 'Başarısız',
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
     * @return array<int, array{id: int, name: string, role_slug: string, role_label: string}>
     */
    public static function users(): array
    {
        return collect(UserManagementDummyData::all())
            ->map(fn (array $user) => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'role_slug' => $user['roles'][0] ?? 'operations_staff',
                'role_label' => $user['role_labels'][0] ?? 'Kullanıcı',
            ])
            ->values()
            ->all();
    }

    /**
     * İleride Excel / PDF / CSV dışa aktarım için payload.
     *
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public static function exportPayload(array $filters, string $format = 'xlsx'): array
    {
        return [
            'format' => $format,
            'filename' => 'crmlog-aktivite-kayitlari-'.Carbon::parse(self::REFERENCE_DATE)->format('Y-m-d').'.'.$format,
            'columns' => [
                'occurred_at', 'user_name', 'role_label', 'module_label',
                'activity_type_label', 'ip_address', 'browser', 'status_label',
            ],
            'rows' => self::filter($filters),
            'meta' => [
                'generated_at' => Carbon::parse(self::REFERENCE_DATE)->toIso8601String(),
                'total' => count(self::filter($filters)),
            ],
        ];
    }

    /**
     * İleride oturum takibi için altyapı.
     *
     * @return array<string, mixed>
     */
    public static function sessionInsights(int $userId): array
    {
        $sessions = collect(self::all())
            ->where('user_id', $userId)
            ->whereIn('activity_type', ['login', 'logout'])
            ->sortByDesc('occurred_at')
            ->take(5)
            ->values();

        return [
            'active_session_count' => random_int(0, 2),
            'last_login_at' => $sessions->firstWhere('activity_type', 'login')['occurred_at'] ?? null,
            'last_logout_at' => $sessions->firstWhere('activity_type', 'logout')['occurred_at'] ?? null,
            'recent_sessions' => $sessions->all(),
        ];
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
                if (($filters['user_id'] ?? 'all') !== 'all' && (int) $log['user_id'] !== (int) $filters['user_id']) {
                    return false;
                }

                if (($filters['role'] ?? 'all') !== 'all' && $log['role_slug'] !== $filters['role']) {
                    return false;
                }

                if (($filters['activity_type'] ?? 'all') !== 'all' && $log['activity_type'] !== $filters['activity_type']) {
                    return false;
                }

                if (($filters['module'] ?? 'all') !== 'all' && $log['module'] !== $filters['module']) {
                    return false;
                }

                if (! empty($filters['ip_address'])) {
                    if (! str_contains($log['ip_address'], trim($filters['ip_address']))) {
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
            'successful_logins' => $all->where('activity_type', 'login')->where('status', 'success')->count(),
            'failed_logins' => $all->where('activity_type', 'login_failed')->count(),
            'password_changes' => $all->where('activity_type', 'password_changed')->count(),
            'permission_changes' => $all->whereIn('activity_type', ['permission_updated', 'role_changed'])->count(),
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

        $users = self::users();
        $activityTypes = array_keys(self::activityTypes());
        $modules = array_keys(self::modules());
        $browsers = [
            ['browser' => 'Chrome 126', 'os' => 'Windows 11'],
            ['browser' => 'Safari 17', 'os' => 'macOS 14'],
            ['browser' => 'Firefox 127', 'os' => 'Ubuntu 24.04'],
            ['browser' => 'Chrome 126', 'os' => 'Android 14'],
            ['browser' => 'Edge 126', 'os' => 'Windows 10'],
            ['browser' => 'Chrome 125', 'os' => 'iOS 17'],
        ];
        $ips = [
            '85.105.42.118', '192.168.1.45', '176.88.12.67', '78.189.55.201', '95.70.33.144',
            '185.24.10.42', '10.0.0.12', '172.16.0.8', '213.74.88.19', '46.1.22.90',
        ];

        $activityModuleMap = [
            'login' => 'auth',
            'logout' => 'auth',
            'login_failed' => 'auth',
            'user_created' => 'users',
            'user_updated' => 'users',
            'role_changed' => 'roles',
            'permission_updated' => 'permissions',
            'password_changed' => 'auth',
            'business_created' => 'businesses',
            'courier_updated' => 'couriers',
            'earning_created' => 'earnings',
            'collection_made' => 'collections',
            'payment_made' => 'payments',
            'invoice_issued' => 'invoices',
            'settings_updated' => 'settings',
        ];

        $records = [];
        $start = Carbon::parse('2025-11-01 07:30:00');

        for ($id = 1; $id <= 320; $id++) {
            $user = $users[($id - 1) % count($users)];
            $activityType = $activityTypes[($id - 1) % count($activityTypes)];
            $module = $activityModuleMap[$activityType] ?? $modules[($id - 1) % count($modules)];
            $browser = $browsers[($id - 1) % count($browsers)];
            $occurredAt = $start->copy()->addHours((int) ($id * 6.5) + ($id % 8))->addMinutes($id % 60);

            $status = match (true) {
                $activityType === 'login_failed' => 'failed',
                in_array($activityType, ['role_changed', 'permission_updated', 'settings_updated'], true) && $id % 9 === 0 => 'warning',
                $id % 53 === 0 => 'failed',
                $id % 21 === 0 => 'warning',
                default => 'success',
            };

            $subjectId = ($id % 45) + 1;
            [$oldValues, $newValues] = self::buildChangeSet($activityType, $module, $subjectId, $user);

            $records[] = [
                'id' => $id,
                'log_name' => $module,
                'event' => $activityType,
                'activity_type' => $activityType,
                'module' => $module,
                'subject_type' => self::subjectTypeFor($module),
                'subject_id' => $subjectId,
                'causer_type' => 'App\\Models\\User',
                'causer_id' => $user['id'],
                'occurred_at' => $occurredAt->toDateTimeString(),
                'user_id' => $user['id'],
                'user_name' => $user['name'],
                'role_slug' => $user['role_slug'],
                'role_label' => $user['role_label'],
                'ip_address' => $ips[$id % count($ips)],
                'user_agent' => 'Mozilla/5.0 ('.$browser['os'].') '.$browser['browser'],
                'browser' => $browser['browser'],
                'operating_system' => $browser['os'],
                'status' => $status,
                'description' => self::descriptionFor($activityType, $module, $user['name']),
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
    private static function buildChangeSet(string $activityType, string $module, int $subjectId, array $user): array
    {
        $old = ['id' => $subjectId, 'module' => $module];
        $new = ['id' => $subjectId, 'module' => $module, 'updated_by' => $user['name']];

        return match ($activityType) {
            'login', 'logout' => [
                [],
                ['user_id' => $user['id'], 'session_id' => 'sess_'.md5((string) $subjectId)],
            ],
            'login_failed' => [
                ['email' => 'unknown@example.com'],
                ['reason' => 'invalid_credentials', 'attempt' => 1],
            ],
            'user_created' => [
                [],
                ['name' => $user['name'], 'email' => 'yeni.kullanici@crmlog.test', 'status' => 'active'],
            ],
            'user_updated', 'courier_updated' => [
                ['phone' => '0532 100 10 01', 'status' => 'active'],
                ['phone' => '0533 200 20 02', 'status' => 'active'],
            ],
            'role_changed' => [
                ['roles' => ['operations_staff']],
                ['roles' => ['operations_manager', 'finance_officer']],
            ],
            'permission_updated' => [
                ['permissions' => ['dashboard.view', 'business.view']],
                ['permissions' => ['dashboard.view', 'business.view', 'business.create', 'report.export']],
            ],
            'password_changed' => [
                ['password_changed_at' => null],
                ['password_changed_at' => Carbon::parse(self::REFERENCE_DATE)->toIso8601String()],
            ],
            'business_created' => [
                [],
                ['company_name' => 'Yeni İşletme Ltd. Şti.', 'status' => 'active'],
            ],
            'earning_created' => [
                [],
                ['reference' => 'HAK-2026-'.str_pad((string) $subjectId, 6, '0', STR_PAD_LEFT), 'amount' => 18500.00],
            ],
            'collection_made' => [
                ['collected_amount' => 0],
                ['collected_amount' => 12500.00, 'payment_method' => 'bank_transfer'],
            ],
            'payment_made' => [
                ['paid_amount' => 0],
                ['paid_amount' => 9800.00, 'status' => 'completed'],
            ],
            'invoice_issued' => [
                ['invoice_status' => 'draft'],
                ['invoice_status' => 'issued', 'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479'],
            ],
            'settings_updated' => [
                ['locale' => 'tr', 'theme' => 'light'],
                ['locale' => 'tr', 'theme' => 'dark'],
            ],
            default => [$old, $new],
        };
    }

    private static function subjectTypeFor(string $module): string
    {
        return match ($module) {
            'users' => 'App\\Models\\User',
            'roles' => 'Spatie\\Permission\\Models\\Role',
            'permissions' => 'Spatie\\Permission\\Models\\Permission',
            'businesses' => 'App\\Modules\\Business\\Models\\Business',
            'couriers' => 'App\\Modules\\Courier\\Models\\Courier',
            'agencies' => 'App\\Modules\\Agency\\Models\\Agency',
            'earnings' => 'App\\Modules\\Finance\\Models\\Earning',
            'collections' => 'App\\Modules\\Finance\\Models\\Collection',
            'payments' => 'App\\Modules\\Finance\\Models\\Payment',
            'invoices' => 'App\\Modules\\Finance\\Models\\Invoice',
            'settings' => 'App\\Models\\Setting',
            default => 'App\\Models\\AuditSubject',
        };
    }

    private static function descriptionFor(string $activityType, string $module, string $userName): string
    {
        $activityLabel = self::activityTypes()[$activityType] ?? $activityType;
        $moduleLabel = self::modules()[$module] ?? $module;

        return "{$userName} — {$activityLabel} ({$moduleLabel})";
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
            'activity_type_label' => self::activityTypes()[$row['activity_type']] ?? $row['activity_type'],
            'status_label' => self::statuses()[$row['status']] ?? $row['status'],
            'date_formatted' => $occurred->format('d.m.Y'),
            'time_formatted' => $occurred->format('H:i:s'),
            'user_profile_route' => route('users.show', $row['user_id']),
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
            'activity_type_label' => $log['activity_type_label'],
            'occurred_at' => $log['date_formatted'].' '.$log['time_formatted'],
            'user_name' => $log['user_name'],
            'role_label' => $log['role_label'],
            'module_label' => $log['module_label'],
            'ip_address' => $log['ip_address'],
            'browser' => $log['browser'],
            'operating_system' => $log['operating_system'],
            'old_values_json' => $log['old_values_json'],
            'new_values_json' => $log['new_values_json'],
            'description' => $log['description'],
            'user_profile_route' => $log['user_profile_route'],
            'session_insights' => self::sessionInsights((int) $log['user_id']),
        ];
    }
}
