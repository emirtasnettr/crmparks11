<?php

namespace App\Modules\User\Data;

class PermissionManagementFormData
{
    /**
     * Yetki matrisinde seçilebilir roller.
     *
     * @return array<string, string>
     */
    public static function selectableRoles(): array
    {
        return [
            'super_admin' => 'Süper Admin',
            'general_manager' => 'Genel Müdür',
            'sales_manager' => 'Satış Müdürü',
            'operations_specialist' => 'Operasyon Uzmanı',
            'business' => 'İşletme',
            'courier' => 'Kurye',
            'agency' => 'Acente',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            'view' => 'Görüntüle',
            'create' => 'Oluştur',
            'update' => 'Güncelle',
            'delete' => 'Sil',
            'export' => 'Dışa Aktar',
            'print' => 'Yazdır',
            'approve' => 'Onayla',
        ];
    }

    /**
     * @param  array<int, string>  $grantedSlugs
     * @return array<int, array<string, mixed>>
     */
    public static function buildMatrix(array $grantedSlugs): array
    {
        $granted = array_flip($grantedSlugs);

        return collect(self::moduleDefinitions())
            ->map(function (array $module) use ($granted) {
                $actions = [];

                foreach (self::actionLabels() as $actionKey => $actionLabel) {
                    $definition = $module['actions'][$actionKey] ?? null;

                    if ($definition === null) {
                        $actions[$actionKey] = [
                            'label' => $actionLabel,
                            'applicable' => false,
                            'granted' => false,
                            'slugs' => [],
                            'primary_slug' => null,
                        ];

                        continue;
                    }

                    $slugs = $definition['slugs'];
                    $isGranted = collect($slugs)->contains(fn (string $slug) => isset($granted[$slug]));

                    $actions[$actionKey] = [
                        'label' => $actionLabel,
                        'applicable' => true,
                        'granted' => $isGranted,
                        'slugs' => $slugs,
                        'primary_slug' => $slugs[0],
                    ];
                }

                return [
                    'key' => $module['key'],
                    'label' => $module['label'],
                    'search_terms' => $module['search_terms'],
                    'actions' => $actions,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function allMatrixPermissionSlugs(): array
    {
        return collect(self::moduleDefinitions())
            ->flatMap(fn (array $module) => collect($module['actions'])
                ->filter()
                ->flatMap(fn (array $action) => $action['slugs']))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function defaultGrantsForRole(string $roleSlug): array
    {
        $all = self::allMatrixPermissionSlugs();

        $operational = self::slugsMatching([
            'dashboard.view',
            'shift_planning.', 'stock.',
            'courier.', 'agency.',
        ]);

        $finance = self::slugsMatching([
            'dashboard.financial', 'finance.',
        ]);

        $reports = self::slugsMatching(['report.']);
        $notifications = self::slugsMatching(['notification.']);

        return match ($roleSlug) {
            'super_admin' => $all,
            'general_manager' => array_values(array_diff($all, self::slugsMatching(['user.', 'permission.']))),
            'sales_manager' => array_values(array_unique(array_merge(
                ['dashboard.view', 'form_application.view'],
                self::slugsWithActions(
                    self::slugsMatching(['business.', 'business_contact.', 'contract.', 'document.']),
                    ['view', 'create', 'update', 'export', 'print']
                ),
                self::slugsWithActions(self::slugsMatching(['report.']), ['view', 'export', 'print']),
                self::slugsWithActions(self::slugsMatching(['notification.']), ['view', 'update']),
            ))),
            'operations_specialist' => array_values(array_unique(array_merge(
                self::slugsWithActions($operational, ['view', 'create', 'update', 'export', 'print', 'delete']),
                self::slugsWithActions($notifications, ['view', 'update']),
                ['dashboard.view', 'form_application.view', 'business.view'],
            ))),
            'business' => [
                'dashboard.view',
                'business.view_own', 'business.export', 'business.print',
                'business_contact.view_own',
                'contract.view_own', 'contract.print',
                'earning.view_own', 'earning.print',
                'document.view_own', 'document.print',
                'notification.view',
            ],
            'courier' => [
                'dashboard.view',
                'courier.view_own', 'courier.print',
                'contract.view_own', 'contract.print',
                'earning.view_own', 'earning.print',
                'document.view_own', 'document.print',
                'notification.view',
            ],
            'agency' => [
                'dashboard.view',
                'agency.view_own', 'agency.export', 'agency.print',
                'courier.view', 'courier.export',
                'contract.view_own', 'contract.print',
                'earning.view_own', 'earning.approve', 'earning.print',
                'document.view_own',
                'notification.view',
            ],
            default => ['dashboard.view'],
        };
    }

    /**
     * @param  array<int, string>  $matrix
     * @return array<string, int>
     */
    public static function summarizeMatrix(array $matrix): array
    {
        $applicable = 0;
        $active = 0;

        foreach ($matrix as $row) {
            foreach ($row['actions'] as $action) {
                if (! $action['applicable']) {
                    continue;
                }

                $applicable++;

                if ($action['granted']) {
                    $active++;
                }
            }
        }

        return [
            'total_roles' => count(self::selectableRoles()),
            'total_permissions' => count(self::allMatrixPermissionSlugs()),
            'active_permissions' => $active,
            'inactive_permissions' => $applicable - $active,
        ];
    }

    /**
     * @param  array<int, string>  $before
     * @param  array<int, string>  $after
     * @return array<string, mixed>
     */
    public static function auditLogPayload(string $roleSlug, array $before, array $after, int $userId = 1): array
    {
        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        return [
            'log_name' => 'permission_changes',
            'description' => "Rol yetkileri güncellendi: {$roleSlug}",
            'subject_type' => 'Spatie\\Permission\\Models\\Role',
            'subject_id' => $roleSlug,
            'causer_id' => $userId,
            'properties' => [
                'role' => $roleSlug,
                'added' => $added,
                'removed' => $removed,
                'before_count' => count($before),
                'after_count' => count($after),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $prefixes
     * @return array<int, string>
     */
    private static function slugsMatching(array $prefixes): array
    {
        return collect(self::allMatrixPermissionSlugs())
            ->filter(function (string $slug) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($slug, $prefix)) {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $slugs
     * @param  array<int, string>  $actions
     * @return array<int, string>
     */
    private static function slugsWithActions(array $slugs, array $actions): array
    {
        return collect($slugs)
            ->filter(function (string $slug) use ($actions) {
                $suffix = substr($slug, strrpos($slug, '.') + 1);

                return in_array($suffix, $actions, true);
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function moduleDefinitions(): array
    {
        return [
            self::module('dashboard', 'Dashboard', ['dashboard'], self::actions('dashboard', ['view'])),
            self::module('businesses', 'İşletmeler', ['işletme', 'business'], self::actions('business', ['view', 'create', 'update', 'delete', 'export', 'print'], ['view' => ['business.view', 'business.view_own']])),
            self::module('contacts', 'Yetkililer', ['yetkili', 'contact'], self::actions('business_contact', ['view', 'create', 'update', 'delete', 'export'], ['view' => ['business_contact.view', 'business_contact.view_own']])),
            self::module('contracts', 'Sözleşmeler', ['sözleşme', 'contract'], self::actions('contract', ['view', 'create', 'update', 'delete', 'export', 'print'], ['view' => ['contract.view', 'contract.view_own']])),
            self::module('shift_planning', 'Vardiya Planlama', ['vardiya', 'shift'], self::actions('shift_planning', ['view', 'create', 'update', 'delete'])),
            self::module('stock', 'Stok Yönetimi', ['stok', 'ekipman', 'zimmet', 'stock'], self::actions('stock', ['view', 'create', 'update', 'delete'])),
            self::module('earnings', 'Hakedişler', ['hakediş', 'earning'], self::actions('earning', ['view', 'create', 'update', 'delete', 'export', 'print', 'approve'], ['view' => ['earning.view', 'earning.view_own']])),
            self::module('documents', 'Evraklar', ['evrak', 'document'], self::actions('document', ['view', 'create', 'update', 'delete', 'export', 'print'], ['view' => ['document.view', 'document.view_own']])),
            self::module('couriers', 'Kuryeler', ['kurye', 'courier'], self::actions('courier', ['view', 'create', 'update', 'delete', 'export', 'print'], ['view' => ['courier.view', 'courier.view_own']])),
            self::module('form_applications', 'Form Başvuruları', ['form başvuru', 'başvuru'], self::actions('form_application', ['view'])),
            self::module('agencies', 'Acenteler', ['acente', 'agency'], self::actions('agency', ['view', 'create', 'update', 'delete', 'export', 'print'], ['view' => ['agency.view', 'agency.view_own']])),
            self::module('finance_dashboard', 'Finans Dashboard', ['finans dashboard'], self::actions('dashboard', ['view'], ['view' => ['dashboard.financial']])),
            self::module('current_accounts', 'Cari Hesaplar', ['cari'], self::actions('finance.current_account', ['view', 'create', 'update', 'delete', 'export', 'print'])),
            self::module('revenues', 'Gelirler', ['gelir'], self::actions('finance.revenue', ['view', 'create', 'update', 'delete', 'export', 'print', 'approve'])),
            self::module('expenses', 'Giderler', ['gider'], self::actions('finance.expense', ['view', 'create', 'update', 'delete', 'export', 'print', 'approve'])),
            self::module('collections', 'Tahsilatlar', ['tahsilat'], self::actions('finance.collection', ['view', 'create', 'update', 'delete', 'export', 'print', 'approve'])),
            self::module('payments', 'Ödemeler', ['ödeme'], self::actions('finance.payment', ['view', 'create', 'update', 'delete', 'export', 'print', 'approve'])),
            self::module('invoices', 'Faturalar', ['fatura'], self::actions('finance.invoice', ['view', 'create', 'update', 'delete', 'export', 'print', 'approve'])),
            self::module('profitability', 'Karlılık Analizi', ['karlılık'], self::actions('finance.profitability', ['view', 'export', 'print'])),
            self::module('cash_flow', 'Nakit Akışı', ['nakit'], self::actions('finance.cash_flow', ['view', 'export', 'print'])),
            self::module('reports', 'Raporlar', ['rapor', 'report'], self::actions('report', ['view', 'export', 'print'])),
            self::module('notifications', 'Bildirimler', ['bildirim'], self::actions('notification', ['view', 'update', 'delete'])),
            self::module('users', 'Kullanıcılar', ['kullanıcı', 'user'], self::actions('user', ['view', 'create', 'update', 'delete', 'export'])),
            self::module('roles', 'Roller', ['rol', 'role'], self::actions('role', ['view', 'create', 'update', 'delete'])),
            self::module('permissions', 'Yetkiler', ['yetki', 'permission'], self::actions('permission', ['view', 'update'])),
            self::module('settings', 'Sistem Ayarları', ['ayar', 'setting'], self::actions('setting', ['view', 'update'], ['view' => ['setting.view', 'activity_log.view']])),
        ];
    }

    /**
     * @param  array<string, array<int, string>>  $overrides
     * @return array<string, array<string, mixed>|null>
     */
    private static function actions(string $prefix, array $enabled, array $overrides = []): array
    {
        $allActions = array_keys(self::actionLabels());
        $map = [];

        foreach ($allActions as $action) {
            if (! in_array($action, $enabled, true)) {
                $map[$action] = null;

                continue;
            }

            $slug = match ($action) {
                'view' => "{$prefix}.view",
                'create' => "{$prefix}.create",
                'update' => "{$prefix}.update",
                'delete' => "{$prefix}.delete",
                'export' => "{$prefix}.export",
                'print' => "{$prefix}.print",
                'approve' => "{$prefix}.approve",
            };

            $map[$action] = [
                'slugs' => $overrides[$action] ?? [$slug],
            ];
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $searchTerms
     * @param  array<string, array<string, mixed>|null>  $actions
     * @return array<string, mixed>
     */
    private static function module(string $key, string $label, array $searchTerms, array $actions): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'search_terms' => $searchTerms,
            'actions' => $actions,
        ];
    }
}
