<?php

namespace App\Modules\User\Data;

use App\Support\DemoData;
use App\Modules\Business\Data\BusinessAssignmentDummyData;
use App\Modules\Business\Data\BusinessDummyData;
use App\Modules\Courier\Data\CourierDummyData;
use Carbon\Carbon;

class UserManagementDummyData
{
    private const REFERENCE_DATE = '2026-07-07';

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $recordsCache = null;

    /**
     * Spatie Permission role slug => görünen ad.
     *
     * @return array<string, string>
     */
    public static function roles(): array
    {
        return [
            'super_admin' => 'Süper Admin',
            'general_manager' => 'Genel Müdür',
            'operations_manager' => 'Operasyon Yöneticisi',
            'finance_officer' => 'Finans Sorumlusu',
            'operations_staff' => 'Operasyon Personeli',
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
            'suspended' => 'Askıda',
            'inactive' => 'Pasif',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function lastLoginFilters(): array
    {
        return [
            'all' => 'Tümü',
            'today' => 'Bugün',
            'week' => 'Bu Hafta',
            'month' => 'Bu Ay',
            'never' => 'Hiç Giriş Yapmamış',
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
     * @return array<int, array{id: int, name: string}>
     */
    public static function couriers(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(CourierDummyData::all())
            ->map(fn (array $courier) => [
                'id' => $courier['id'],
                'name' => $courier['full_name'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function agencies(): array
    {
        return BusinessAssignmentDummyData::agencies();
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        $reference = Carbon::parse(self::REFERENCE_DATE);

        return collect(self::all())
            ->filter(function (array $user) use ($filters, $reference) {
                if (! empty($filters['search'])) {
                    $needle = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $user['full_name'],
                        $user['email'],
                        $user['phone'],
                    ]));

                    if (! str_contains($haystack, $needle)) {
                        return false;
                    }
                }

                if (($filters['role'] ?? 'all') !== 'all' && ! in_array($filters['role'], $user['roles'], true)) {
                    return false;
                }

                if (($filters['status'] ?? 'all') !== 'all' && $user['status'] !== $filters['status']) {
                    return false;
                }

                $lastLoginFilter = $filters['last_login'] ?? 'all';

                if ($lastLoginFilter === 'never' && $user['last_login_at'] !== null) {
                    return false;
                }

                if ($lastLoginFilter !== 'all' && $lastLoginFilter !== 'never' && $user['last_login_at'] !== null) {
                    $login = Carbon::parse($user['last_login_at']);

                    $matches = match ($lastLoginFilter) {
                        'today' => $login->isSameDay($reference),
                        'week' => $login->greaterThanOrEqualTo($reference->copy()->startOfWeek()),
                        'month' => $login->greaterThanOrEqualTo($reference->copy()->startOfMonth()),
                        default => true,
                    };

                    if (! $matches) {
                        return false;
                    }
                }

                if ($lastLoginFilter !== 'never' && $lastLoginFilter !== 'all' && $user['last_login_at'] === null) {
                    return false;
                }

                return true;
            })
            ->sortByDesc('id')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, int>
     */
    public static function summarize(array $filters = []): array
    {
        $reference = Carbon::parse(self::REFERENCE_DATE);
        $users = empty($filters) ? self::all() : self::filter($filters);

        return [
            'total' => count($users),
            'active' => collect($users)->where('status', 'active')->whereNull('deleted_at')->count(),
            'inactive' => collect($users)->filter(fn (array $u) => $u['status'] === 'inactive' || $u['deleted_at'] !== null)->count(),
            'logged_in_today' => collect($users)->filter(function (array $user) use ($reference) {
                return $user['last_login_at'] !== null
                    && Carbon::parse($user['last_login_at'])->isSameDay($reference);
            })->count(),
        ];
    }

    public static function find(int $id): ?array
    {
        $user = collect(self::all())->firstWhere('id', $id);

        if ($user === null) {
            return null;
        }

        return array_merge($user, [
            'recent_logins' => self::recentLoginsFor($user),
            'permissions' => self::permissionsForRoles($user['roles']),
            'sessions' => self::sessionsFor($user),
            'activity_log' => self::activityLogFor($user),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

if (self::$recordsCache !== null) {
            return self::$recordsCache;
        }

        self::$recordsCache = collect(self::raw())
            ->map(fn (array $row) => self::enrich($row))
            ->values()
            ->all();

        return self::$recordsCache;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function enrich(array $row): array
    {
        $roles = $row['roles'];
        $roleLabels = collect($roles)
            ->map(fn (string $role) => self::roles()[$role] ?? $role)
            ->values()
            ->all();

        $firstName = $row['first_name'];
        $lastName = $row['last_name'];
        $fullName = trim("{$firstName} {$lastName}");

        $linkedUnit = self::resolveLinkedUnit($row);

        $lastLoginAt = $row['last_login_at'] ?? null;

        return [
            'id' => $row['id'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'email' => $row['email'],
            'phone' => $row['phone'],
            'roles' => $roles,
            'role_labels' => $roleLabels,
            'status' => $row['status'],
            'status_label' => self::statuses()[$row['status']] ?? $row['status'],
            'user_type' => $row['user_type'],
            'user_type_label' => match ($row['user_type']) {
                'internal' => 'Dahili Kullanıcı',
                'courier' => 'Kurye',
                'business' => 'İşletme',
                'agency' => 'Acente',
                default => 'Kullanıcı',
            },
            'avatar_initials' => self::initials($firstName, $lastName),
            'avatar_color' => $row['avatar_color'] ?? self::avatarColor($row['id']),
            'linked_business_id' => $row['linked_business_id'] ?? null,
            'linked_business_name' => $row['linked_business_name'] ?? null,
            'linked_courier_id' => $row['linked_courier_id'] ?? null,
            'linked_courier_name' => $row['linked_courier_name'] ?? null,
            'linked_agency_id' => $row['linked_agency_id'] ?? null,
            'linked_agency_name' => $row['linked_agency_name'] ?? null,
            'linked_unit' => $linkedUnit,
            'last_login_at' => $lastLoginAt,
            'last_login_formatted' => $lastLoginAt
                ? Carbon::parse($lastLoginAt)->format('d.m.Y H:i')
                : '—',
            'last_login_ip' => $row['last_login_ip'] ?? null,
            'two_factor_enabled' => (bool) ($row['two_factor_enabled'] ?? false),
            'two_factor_method' => $row['two_factor_method'] ?? null,
            'deleted_at' => $row['deleted_at'] ?? null,
            'created_at' => $row['created_at'] ?? '2025-01-15 09:00:00',
            'created_at_formatted' => Carbon::parse($row['created_at'] ?? '2025-01-15 09:00:00')->format('d.m.Y'),
            'email_verified_at' => $row['email_verified_at'] ?? $row['created_at'] ?? '2025-01-15 09:00:00',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function resolveLinkedUnit(array $row): string
    {
        $parts = array_filter([
            $row['linked_business_name'] ?? null,
            $row['linked_courier_name'] ?? null,
            $row['linked_agency_name'] ?? null,
        ]);

        if ($parts !== []) {
            return implode(' · ', $parts);
        }

        if (($row['user_type'] ?? 'internal') === 'internal') {
            return 'Merkez Operasyon';
        }

        return '—';
    }

    private static function initials(string $firstName, string $lastName): string
    {
        return mb_strtoupper(mb_substr($firstName, 0, 1).mb_substr($lastName, 0, 1));
    }

    private static function avatarColor(int $id): string
    {
        $colors = [
            'bg-blue-600', 'bg-violet-600', 'bg-emerald-600', 'bg-amber-600',
            'bg-rose-600', 'bg-cyan-600', 'bg-indigo-600', 'bg-teal-600',
        ];

        return $colors[$id % count($colors)];
    }

    /**
     * @param  array<string, mixed>  $user
     * @return array<int, array<string, mixed>>
     */
    private static function recentLoginsFor(array $user): array
    {
        if ($user['last_login_at'] === null) {
            return [];
        }

        $base = Carbon::parse($user['last_login_at']);
        $devices = ['Chrome / macOS', 'Safari / iOS', 'Chrome / Windows', 'Firefox / Linux'];

        return collect(range(0, 4))
            ->map(fn (int $index) => [
                'logged_in_at' => $base->copy()->subDays($index * 2)->format('d.m.Y H:i'),
                'ip' => $index === 0 ? ($user['last_login_ip'] ?? '185.24.10.'.($user['id'] % 200)) : '185.24.10.'.(($user['id'] + $index) % 200),
                'device' => $devices[$index % count($devices)],
                'location' => $index % 2 === 0 ? 'İstanbul, TR' : 'Ankara, TR',
            ])
            ->all();
    }

    /**
     * Spatie Permission uyumlu izin listesi (role slug bazlı).
     *
     * @param  array<int, string>  $roles
     * @return array<int, string>
     */
    private static function permissionsForRoles(array $roles): array
    {
        $map = [
            'super_admin' => [
                'dashboard.view', 'dashboard.financial', 'user.view', 'user.create', 'user.update', 'user.delete',
                'setting.view', 'setting.update', 'activity_log.view',
            ],
            'general_manager' => [
                'dashboard.view', 'dashboard.financial', 'business.view', 'courier.view', 'agency.view',
                'earning.view', 'earning.approve', 'report.view', 'report.export', 'activity_log.view',
            ],
            'operations_manager' => [
                'dashboard.view', 'business.view', 'business.create', 'business.update',
                'courier.view', 'courier.create', 'courier.update', 'agency.view', 'agency.create', 'agency.update',
                'assignment.view', 'assignment.create', 'assignment.update',
            ],
            'finance_officer' => [
                'dashboard.view', 'dashboard.financial', 'earning.view', 'earning.approve', 'report.view', 'report.export',
            ],
            'operations_staff' => [
                'dashboard.view', 'business.view', 'courier.view', 'agency.view', 'assignment.view',
            ],
            'business' => ['dashboard.view', 'business.view_own', 'contract.view_own', 'earning.view_own'],
            'courier' => ['dashboard.view', 'courier.view_own', 'earning.view_own', 'contract.view_own'],
            'agency' => ['dashboard.view', 'agency.view_own', 'courier.view', 'earning.view_own', 'contract.view_own'],
        ];

        return collect($roles)
            ->flatMap(fn (string $role) => $map[$role] ?? [])
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $user
     * @return array<int, array<string, mixed>>
     */
    private static function sessionsFor(array $user): array
    {
        $reference = Carbon::parse(self::REFERENCE_DATE);

        return [
            [
                'device' => 'Chrome 126 / macOS Sonoma',
                'ip' => $user['last_login_ip'] ?? '185.24.10.42',
                'last_active' => $reference->format('d.m.Y H:i'),
                'current' => true,
            ],
            [
                'device' => 'Safari / iPhone 15',
                'ip' => '176.88.12.'.($user['id'] % 90 + 10),
                'last_active' => $reference->copy()->subDays(3)->format('d.m.Y H:i'),
                'current' => false,
            ],
            [
                'device' => 'Chrome / Windows 11',
                'ip' => '78.189.45.'.($user['id'] % 90 + 10),
                'last_active' => $reference->copy()->subDays(12)->format('d.m.Y H:i'),
                'current' => false,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $user
     * @return array<int, array<string, mixed>>
     */
    private static function activityLogFor(array $user): array
    {
        $reference = Carbon::parse(self::REFERENCE_DATE);

        return [
            [
                'action' => 'Giriş yapıldı',
                'description' => 'Başarılı oturum açma',
                'performed_at' => $reference->format('d.m.Y H:i'),
                'ip' => $user['last_login_ip'] ?? '185.24.10.42',
            ],
            [
                'action' => 'Profil güncellendi',
                'description' => 'Telefon numarası değiştirildi',
                'performed_at' => $reference->copy()->subDays(5)->format('d.m.Y H:i'),
                'ip' => '185.24.10.42',
            ],
            [
                'action' => 'Rol atandı',
                'description' => implode(', ', $user['role_labels']),
                'performed_at' => $reference->copy()->subDays(30)->format('d.m.Y H:i'),
                'ip' => '10.0.0.12',
            ],
            [
                'action' => 'Şifre sıfırlandı',
                'description' => 'Yönetici tarafından şifre sıfırlama',
                'performed_at' => $reference->copy()->subMonths(2)->format('d.m.Y H:i'),
                'ip' => '10.0.0.5',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function raw(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

$reference = Carbon::parse(self::REFERENCE_DATE);
        $businesses = self::businesses();
        $couriers = self::couriers();
        $agencies = self::agencies();

        $firstNames = [
            'Ahmet', 'Mehmet', 'Ayşe', 'Fatma', 'Elif', 'Zeynep', 'Can', 'Deniz', 'Emre', 'Burak',
            'Selin', 'Merve', 'Oğuz', 'Kaan', 'Ece', 'Berk', 'Cem', 'Derya', 'Furkan', 'Gizem',
            'Hakan', 'İrem', 'Jale', 'Kemal', 'Lale', 'Murat', 'Nazlı', 'Onur', 'Pınar', 'Rıza',
            'Serkan', 'Tuğba', 'Umut', 'Volkan', 'Yasin', 'Zehra', 'Arda', 'Buse', 'Cihan', 'Defne',
            'Eren', 'Filiz', 'Gökhan', 'Hande', 'İlker',
        ];

        $lastNames = [
            'Yılmaz', 'Kaya', 'Demir', 'Çelik', 'Şahin', 'Yıldız', 'Öztürk', 'Aydın', 'Arslan', 'Doğan',
            'Kılıç', 'Aslan', 'Çetin', 'Koç', 'Kurt', 'Özdemir', 'Polat', 'Erdoğan', 'Akın', 'Güneş',
            'Yalçın', 'Tekin', 'Mutlu', 'Sezer', 'Vural', 'Bilgin', 'Tunç', 'Işık', 'Duman', 'Karaca',
            'Başaran', 'Uçar', 'Sarı', 'Korkmaz', 'Özkan', 'Gencer', 'Aktaş', 'Bulut', 'Çakır', 'Eren',
            'Fidan', 'Gökalp', 'Hacıoğlu', 'İnan', 'Jandarma',
        ];

        $rolePool = [
            ['roles' => ['super_admin'], 'user_type' => 'internal'],
            ['roles' => ['general_manager'], 'user_type' => 'internal'],
            ['roles' => ['operations_manager'], 'user_type' => 'internal'],
            ['roles' => ['finance_officer'], 'user_type' => 'internal'],
            ['roles' => ['operations_staff'], 'user_type' => 'internal'],
            ['roles' => ['business'], 'user_type' => 'business'],
            ['roles' => ['courier'], 'user_type' => 'courier'],
            ['roles' => ['agency'], 'user_type' => 'agency'],
        ];

        $statuses = ['active', 'active', 'active', 'active', 'suspended', 'inactive'];
        $loginOffsets = [0, 0, 1, 2, 3, 5, 7, 10, 14, 21, 30, 45, 60, 90, null, null];

        $records = [];

        for ($i = 1; $i <= 45; $i++) {
            $firstName = $firstNames[$i - 1];
            $lastName = $lastNames[$i - 1];
            $roleConfig = $rolePool[($i - 1) % count($rolePool)];

            if ($i % 11 === 0) {
                $roleConfig = ['roles' => ['operations_manager', 'finance_officer'], 'user_type' => 'internal'];
            }

            if ($i % 13 === 0) {
                $roleConfig = ['roles' => ['operations_staff', 'courier'], 'user_type' => 'internal'];
            }

            $status = $statuses[$i % count($statuses)];
            if ($i % 17 === 0) {
                $status = 'inactive';
            }

            $loginOffset = $loginOffsets[$i % count($loginOffsets)];
            $lastLoginAt = $loginOffset === null
                ? null
                : $reference->copy()->subDays($loginOffset)->setTime(8 + ($i % 10), ($i * 7) % 60)->toDateTimeString();

            $business = $businesses[($i - 1) % count($businesses)];
            $courier = $couriers[($i - 1) % count($couriers)];
            $agency = $agencies[($i - 1) % count($agencies)];

            $linkedBusiness = in_array('business', $roleConfig['roles'], true) ? $business : null;
            $linkedCourier = in_array('courier', $roleConfig['roles'], true) && $roleConfig['user_type'] === 'courier'
                ? $courier
                : ($i % 13 === 0 ? $courier : null);
            $linkedAgency = in_array('agency', $roleConfig['roles'], true) ? $agency : null;

            $records[] = [
                'id' => $i,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => self::emailFor($firstName, $lastName, $i),
                'phone' => sprintf('05%02d %03d %02d %02d', 30 + ($i % 10), 100 + $i, 10 + ($i % 80), $i % 100),
                'roles' => $roleConfig['roles'],
                'user_type' => $roleConfig['user_type'],
                'status' => $status,
                'avatar_color' => self::avatarColor($i),
                'linked_business_id' => $linkedBusiness['id'] ?? null,
                'linked_business_name' => $linkedBusiness['name'] ?? null,
                'linked_courier_id' => $linkedCourier['id'] ?? null,
                'linked_courier_name' => $linkedCourier['name'] ?? null,
                'linked_agency_id' => $linkedAgency['id'] ?? null,
                'linked_agency_name' => $linkedAgency['name'] ?? null,
                'last_login_at' => $lastLoginAt,
                'last_login_ip' => $lastLoginAt ? '185.24.'.(($i % 200) + 1).'.'.(($i * 3) % 254 + 1) : null,
                'two_factor_enabled' => $i % 7 === 0,
                'two_factor_method' => $i % 7 === 0 ? ($i % 2 === 0 ? 'app' : 'sms') : null,
                'deleted_at' => $status === 'inactive' && $i % 5 === 0
                    ? $reference->copy()->subDays(10)->toDateTimeString()
                    : null,
                'created_at' => $reference->copy()->subMonths(6)->addDays($i)->toDateTimeString(),
            ];
        }

        return $records;
    }

    private static function emailFor(string $firstName, string $lastName, int $id): string
    {
        $slug = mb_strtolower(str_replace(
            ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'İ', 'Ö', 'Ş', 'Ü'],
            ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'o', 's', 'u'],
            "{$firstName}.{$lastName}"
        ));

        $slug = preg_replace('/[^a-z0-9.]/', '', $slug) ?: "kullanici{$id}";

        return "{$slug}{$id}@crmlog.test";
    }
}
