<?php

namespace App\Modules\User\Data;

use Carbon\Carbon;

class RoleManagementDummyData
{
    private const REFERENCE_DATE = '2026-07-07';

    private const GUARD_NAME = 'web';

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $recordsCache = null;

    /**
     * Spatie Permission uyumlu tüm izinler.
     *
     * @return array<int, string>
     */
    public static function allPermissions(): array
    {
        return [
            'dashboard.view',
            'dashboard.financial',
            'business.view', 'business.create', 'business.update', 'business.delete', 'business.view_own',
            'courier.view', 'courier.create', 'courier.update', 'courier.delete', 'courier.view_own',
            'agency.view', 'agency.create', 'agency.update', 'agency.delete', 'agency.view_own',
            'assignment.view', 'assignment.create', 'assignment.update', 'assignment.delete',
            'contract.view', 'contract.create', 'contract.update', 'contract.delete', 'contract.view_own',
            'earning.view', 'earning.create', 'earning.update', 'earning.delete', 'earning.approve', 'earning.view_own',
            'report.view', 'report.export',
            'user.view', 'user.create', 'user.update', 'user.delete',
            'setting.view', 'setting.update',
            'activity_log.view',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Pasif',
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        return collect(self::all())
            ->filter(function (array $role) use ($filters) {
                if (! empty($filters['search'])) {
                    $needle = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $role['display_name'],
                        $role['name'],
                        $role['description'],
                    ]));

                    if (! str_contains($haystack, $needle)) {
                        return false;
                    }
                }

                if (($filters['status'] ?? 'all') !== 'all' && $role['status'] !== $filters['status']) {
                    return false;
                }

                return true;
            })
            ->sortBy('id')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, int>
     */
    public static function summarize(array $filters = []): array
    {
        $roles = empty($filters) ? self::all() : self::filter($filters);

        return [
            'total_roles' => count($roles),
            'active_roles' => collect($roles)->where('status', 'active')->count(),
            'total_users' => count(UserManagementDummyData::all()),
            'total_permissions' => count(self::allPermissions()),
        ];
    }

    public static function find(int $id): ?array
    {
        $role = collect(self::all())->firstWhere('id', $id);

        if ($role === null) {
            return null;
        }

        return array_merge($role, [
            'assigned_users' => self::assignedUsersFor($role['name']),
            'permission_groups' => self::groupPermissions($role['permissions']),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
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
        $permissions = self::permissionsForRole($row['name']);
        $userCount = self::userCountForRole($row['name']);

        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'display_name' => $row['display_name'],
            'description' => $row['description'],
            'guard_name' => self::GUARD_NAME,
            'status' => $row['status'],
            'status_label' => self::statuses()[$row['status']] ?? $row['status'],
            'is_system' => (bool) $row['is_system'],
            'is_deletable' => (bool) ($row['is_deletable'] ?? false),
            'can_deactivate' => (bool) ($row['can_deactivate'] ?? true),
            'icon' => $row['icon'] ?? 'shield',
            'color' => $row['color'] ?? 'gray',
            'user_count' => $userCount,
            'permission_count' => count($permissions),
            'permissions' => $permissions,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'created_at_formatted' => Carbon::parse($row['created_at'])->format('d.m.Y'),
            'updated_at_formatted' => Carbon::parse($row['updated_at'])->format('d.m.Y H:i'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function permissionsForRole(string $slug): array
    {
        $map = self::rolePermissionMap();

        return $map[$slug] ?? [];
    }

    /**
     * Spatie role slug => permission listesi.
     *
     * @return array<string, array<int, string>>
     */
    private static function rolePermissionMap(): array
    {
        $all = self::allPermissions();

        return [
            'super_admin' => $all,
            'general_manager' => array_values(array_filter(
                $all,
                fn (string $p) => ! str_starts_with($p, 'user.') && ! str_starts_with($p, 'setting.')
            )),
            'operations_manager' => [
                'dashboard.view',
                'business.view', 'business.create', 'business.update',
                'courier.view', 'courier.create', 'courier.update',
                'agency.view', 'agency.create', 'agency.update',
                'assignment.view', 'assignment.create', 'assignment.update',
                'contract.view', 'contract.create', 'contract.update',
                'earning.view', 'earning.create', 'earning.update',
            ],
            'finance_officer' => [
                'dashboard.view', 'dashboard.financial',
                'earning.view', 'earning.approve', 'report.view', 'report.export',
            ],
            'operations_staff' => [
                'dashboard.view',
                'business.view', 'courier.view', 'agency.view', 'assignment.view',
            ],
            'business' => [
                'dashboard.view', 'business.view_own', 'contract.view_own', 'earning.view_own',
            ],
            'courier' => [
                'dashboard.view', 'courier.view_own', 'earning.view_own', 'contract.view_own',
            ],
            'agency' => [
                'dashboard.view', 'agency.view_own', 'courier.view', 'earning.view_own', 'contract.view_own',
            ],
            'regional_coordinator' => [
                'dashboard.view', 'business.view', 'courier.view', 'agency.view', 'assignment.view', 'report.view',
            ],
            'reporting_analyst' => [
                'dashboard.view', 'dashboard.financial', 'report.view', 'report.export', 'earning.view',
            ],
        ];
    }

    private static function userCountForRole(string $slug): int
    {
        return collect(UserManagementDummyData::all())
            ->filter(fn (array $user) => in_array($slug, $user['roles'], true))
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function assignedUsersFor(string $slug): array
    {
        return collect(UserManagementDummyData::all())
            ->filter(fn (array $user) => in_array($slug, $user['roles'], true))
            ->take(10)
            ->map(fn (array $user) => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'status' => $user['status'],
                'avatar_initials' => $user['avatar_initials'],
                'avatar_color' => $user['avatar_color'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<string, array<int, string>>
     */
    private static function groupPermissions(array $permissions): array
    {
        $groups = [];

        foreach ($permissions as $permission) {
            $module = explode('.', $permission)[0];
            $groups[$module][] = $permission;
        }

        ksort($groups);

        return $groups;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function raw(): array
    {
        $reference = Carbon::parse(self::REFERENCE_DATE);

        return [
            [
                'id' => 1,
                'name' => 'super_admin',
                'display_name' => 'Süper Admin',
                'description' => 'Sistemin tüm modüllerine ve ayarlarına tam erişim. Silinemez.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => false,
                'icon' => 'shield-check',
                'color' => 'rose',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => $reference->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id' => 2,
                'name' => 'general_manager',
                'display_name' => 'Genel Müdür',
                'description' => 'Finans, operasyon ve raporlama modüllerine üst düzey erişim.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'briefcase',
                'color' => 'violet',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => $reference->copy()->subDays(5)->toDateTimeString(),
            ],
            [
                'id' => 3,
                'name' => 'operations_manager',
                'display_name' => 'Operasyon Yöneticisi',
                'description' => 'İşletme, kurye, acente ve atama süreçlerini yönetir.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'cog',
                'color' => 'blue',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => $reference->copy()->subDays(8)->toDateTimeString(),
            ],
            [
                'id' => 4,
                'name' => 'finance_officer',
                'display_name' => 'Finans Sorumlusu',
                'description' => 'Gelir, gider, tahsilat ve finansal raporlara erişim.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'currency',
                'color' => 'emerald',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => $reference->copy()->subDays(10)->toDateTimeString(),
            ],
            [
                'id' => 5,
                'name' => 'operations_staff',
                'display_name' => 'Operasyon Personeli',
                'description' => 'Operasyon modüllerinde görüntüleme ve temel işlem yetkileri.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'users',
                'color' => 'cyan',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => $reference->copy()->subDays(12)->toDateTimeString(),
            ],
            [
                'id' => 6,
                'name' => 'business',
                'display_name' => 'İşletme',
                'description' => 'İşletme kullanıcıları için kendi verilerine erişim.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'building',
                'color' => 'amber',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => $reference->copy()->subDays(15)->toDateTimeString(),
            ],
            [
                'id' => 7,
                'name' => 'courier',
                'display_name' => 'Kurye',
                'description' => 'Kurye kullanıcıları için hakediş ve sözleşme görüntüleme.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'truck',
                'color' => 'indigo',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => $reference->copy()->subDays(18)->toDateTimeString(),
            ],
            [
                'id' => 8,
                'name' => 'agency',
                'display_name' => 'Acente',
                'description' => 'Acente kullanıcıları için kurye ve hakediş takibi.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'office',
                'color' => 'teal',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => $reference->copy()->subDays(20)->toDateTimeString(),
            ],
            [
                'id' => 9,
                'name' => 'regional_coordinator',
                'display_name' => 'Bölge Koordinatörü',
                'description' => 'Özel oluşturulmuş rol. Bölgesel operasyon koordinasyonu.',
                'status' => 'active',
                'is_system' => false,
                'is_deletable' => true,
                'can_deactivate' => true,
                'icon' => 'map',
                'color' => 'primary',
                'created_at' => '2025-06-10 10:00:00',
                'updated_at' => $reference->copy()->subDays(3)->toDateTimeString(),
            ],
            [
                'id' => 10,
                'name' => 'reporting_analyst',
                'display_name' => 'Raporlama Analisti',
                'description' => 'Özel oluşturulmuş rol. Finansal raporlama ve analiz.',
                'status' => 'inactive',
                'is_system' => false,
                'is_deletable' => true,
                'can_deactivate' => true,
                'icon' => 'chart',
                'color' => 'slate',
                'created_at' => '2025-11-20 14:30:00',
                'updated_at' => $reference->copy()->subDays(1)->toDateTimeString(),
            ],
        ];
    }
}
