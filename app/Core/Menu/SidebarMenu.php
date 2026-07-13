<?php

namespace App\Core\Menu;

use App\Modules\Agency\Support\AgencyFeatures;
use App\Modules\Business\Support\BusinessFeatures;
use App\Modules\Courier\Support\CourierFeatures;
use Illuminate\Support\Facades\Auth;

class SidebarMenu
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function items(): array
    {
        return array_values(array_filter([
            [
                'key' => 'dashboard',
                'type' => 'link',
                'label' => 'Dashboard',
                'icon' => 'chart-bar',
                'route' => 'dashboard',
                'active' => ['dashboard'],
                'permission' => 'dashboard.view',
            ],
            [
                'key' => 'businesses',
                'type' => 'link',
                'label' => 'İşletmeler',
                'icon' => 'building-office-2',
                'route' => 'businesses.index',
                'active' => ['businesses.index', 'businesses.show', 'businesses.assignments.*'],
                'permission' => 'business.view',
                'roles' => ['operations_specialist'],
            ],
            [
                'key' => 'businesses',
                'type' => 'group',
                'label' => 'İşletmeler',
                'icon' => 'building-office-2',
                'permission' => 'business.view',
                'except_roles' => ['operations_specialist'],
                'active' => Auth::user()?->hasRole('sales_manager')
                    ? ['businesses.index', 'businesses.create', 'businesses.show', 'businesses.edit', 'businesses.contacts.*']
                    : ['businesses.*'],
                'children' => array_values(array_filter([
                    ['label' => 'İşletmeler', 'route' => 'businesses.index', 'active' => ['businesses.index', 'businesses.create', 'businesses.show', 'businesses.edit']],
                    [
                        'label' => 'Yetkililer',
                        'route' => 'businesses.contacts.index',
                        'active' => ['businesses.contacts.*'],
                    ],
                    [
                        'label' => 'Sözleşmeler',
                        'route' => 'businesses.contracts.index',
                        'active' => ['businesses.contracts.*'],
                        'except_roles' => ['sales_manager'],
                    ],
                    BusinessFeatures::earningsEnabled()
                        ? [
                            'label' => 'Hakedişler',
                            'route' => 'businesses.earnings.index',
                            'active' => ['businesses.earnings.*'],
                            'except_roles' => ['sales_manager'],
                        ]
                        : null,
                    [
                        'label' => 'Evraklar',
                        'route' => 'businesses.documents.index',
                        'active' => ['businesses.documents.*'],
                        'except_roles' => ['sales_manager'],
                    ],
                    [
                        'label' => 'Hareket Geçmişi',
                        'route' => 'businesses.activities.index',
                        'active' => ['businesses.activities.*'],
                        'except_roles' => ['sales_manager'],
                    ],
                ])),
            ],
            [
                'key' => 'shift_planning',
                'type' => 'link',
                'label' => 'Vardiya Planlama',
                'icon' => 'calendar-days',
                'route' => 'shift-planning.index',
                'active' => ['shift-planning.*'],
                'permission' => 'shift_planning.view',
            ],
            [
                'key' => 'business_contracts',
                'type' => 'link',
                'label' => 'Sözleşmeler',
                'icon' => 'document-text',
                'route' => 'businesses.contracts.index',
                'active' => ['businesses.contracts.*'],
                'permission' => 'business.view',
                'roles' => ['sales_manager'],
            ],
            [
                'key' => 'business_documents',
                'type' => 'link',
                'label' => 'Evraklar',
                'icon' => 'folder',
                'route' => 'businesses.documents.index',
                'active' => ['businesses.documents.*'],
                'permission' => 'business.view',
                'roles' => ['sales_manager'],
            ],
            [
                'key' => 'business_activities',
                'type' => 'link',
                'label' => 'Hareket Geçmişi',
                'icon' => 'clock',
                'route' => 'businesses.activities.index',
                'active' => ['businesses.activities.*'],
                'permission' => 'business.view',
                'roles' => ['sales_manager'],
            ],
            [
                'key' => 'reports',
                'type' => 'link',
                'label' => 'Raporlar',
                'icon' => 'document-chart-bar',
                'route' => 'reports.index',
                'active' => ['reports.*'],
                'permission' => 'report.view',
            ],
            [
                'key' => 'form_applications',
                'type' => 'link',
                'label' => 'Form Başvuruları',
                'icon' => 'clipboard-document-check',
                'route' => 'form-applications.index',
                'active' => ['form-applications.*'],
                'permission' => 'form_application.view',
            ],
            [
                'key' => 'couriers',
                'type' => 'group',
                'label' => 'Kuryeler',
                'icon' => 'truck',
                'permission' => 'courier.view',
                'active' => ['couriers.*'],
                'children' => array_values(array_filter([
                    ['label' => 'Kuryeler', 'route' => 'couriers.index', 'active' => ['couriers.index', 'couriers.create']],
                    ['label' => 'Belgeler', 'route' => 'couriers.documents.index', 'active' => ['couriers.documents.*']],
                    CourierFeatures::earningsEnabled()
                        ? [
                            'label' => 'Hakedişler',
                            'route' => 'couriers.earnings.index',
                            'active' => ['couriers.earnings.*'],
                            'except_roles' => ['operations_specialist'],
                        ]
                        : null,
                    ['label' => 'Çalışma Geçmişi', 'route' => 'couriers.work-history.index', 'active' => ['couriers.work-history.*']],
                    ['label' => 'Araç Bilgileri', 'route' => 'couriers.vehicles.index', 'active' => ['couriers.vehicles.*']],
                    ['label' => 'Banka Bilgileri', 'route' => 'couriers.bank-accounts.index', 'active' => ['couriers.bank-accounts.*']],
                    ['label' => 'Hareket Geçmişi', 'route' => 'couriers.activities.index', 'active' => ['couriers.activities.*']],
                ])),
            ],
            [
                'key' => 'agencies',
                'type' => 'group',
                'label' => 'Acenteler',
                'icon' => 'building-storefront',
                'permission' => 'agency.view',
                'active' => ['agencies.*'],
                'children' => array_values(array_filter([
                    ['label' => 'Acenteler', 'route' => 'agencies.index', 'active' => ['agencies.index', 'agencies.create']],
                    ['label' => 'Yetkililer', 'route' => 'agencies.contacts.index', 'active' => ['agencies.contacts.*']],
                    ['label' => 'Kuryeler', 'route' => 'agencies.couriers.index', 'active' => ['agencies.couriers.*']],
                    ['label' => 'Sözleşmeler', 'route' => 'agencies.contracts.index', 'active' => ['agencies.contracts.*']],
                    AgencyFeatures::earningsEnabled()
                        ? [
                            'label' => 'Hakedişler',
                            'route' => 'agencies.earnings.index',
                            'active' => ['agencies.earnings.*'],
                            'except_roles' => ['operations_specialist'],
                        ]
                        : null,
                    ['label' => 'Evraklar', 'route' => 'agencies.documents.index', 'active' => ['agencies.documents.*']],
                    ['label' => 'Hareket Geçmişi', 'route' => 'agencies.activities.index', 'active' => ['agencies.activities.*']],
                ])),
            ],
            [
                'key' => 'finance',
                'type' => 'group',
                'label' => 'Finans',
                'icon' => 'banknotes',
                'permission' => 'dashboard.financial',
                'active' => ['finance.*'],
                'children' => [
                    ['label' => 'Dashboard', 'route' => 'finance.dashboard.index', 'active' => ['finance.dashboard.*']],
                    ['label' => 'Cari Hesaplar', 'route' => 'finance.current-accounts.index', 'active' => ['finance.current-accounts.*']],
                    ['label' => 'Gelirler', 'route' => 'finance.revenues.index', 'active' => ['finance.revenues.*']],
                    ['label' => 'Giderler', 'route' => 'finance.expenses.index', 'active' => ['finance.expenses.*']],
                    ['label' => 'Tahsilatlar', 'route' => 'finance.collections.index', 'active' => ['finance.collections.*']],
                    ['label' => 'Ödemeler', 'route' => 'finance.payments.index', 'active' => ['finance.payments.*']],
                    ['label' => 'Faturalar', 'route' => 'finance.invoices.index', 'active' => ['finance.invoices.*']],
                    ['label' => 'Karlılık Analizi', 'route' => 'finance.profitability.index', 'active' => ['finance.profitability.*']],
                    ['label' => 'Nakit Akışı', 'route' => 'finance.cash-flow.index', 'active' => ['finance.cash-flow.*']],
                    [
                        'label' => 'Hareket Geçmişi',
                        'route' => 'finance.activity-log.index',
                        'active' => ['finance.activity-log.*'],
                        'roles' => ['super_admin', 'general_manager'],
                    ],
                ],
            ],
            [
                'key' => 'settings',
                'type' => 'group',
                'label' => 'Ayarlar',
                'icon' => 'cog-6-tooth',
                'except_roles' => ['sales_manager', 'operations_specialist'],
                'active' => [
                    'settings.*',
                    'users.*',
                    'roles.*',
                    'permissions.*',
                    'notifications.*',
                    'form-builder.*',
                    'landing-page-builder.*',
                ],
                'children' => [
                    [
                        'label' => 'Sistem Ayarları',
                        'route' => 'settings.index',
                        'active' => ['settings.*'],
                        'roles' => ['super_admin'],
                    ],
                    [
                        'label' => 'Kullanıcılar',
                        'route' => 'users.index',
                        'active' => ['users.index', 'users.show', 'users.create', 'users.edit'],
                        'permission' => 'user.view',
                    ],
                    [
                        'label' => 'Roller',
                        'route' => 'roles.index',
                        'active' => ['roles.*'],
                        'permission' => 'user.view',
                    ],
                    [
                        'label' => 'Yetkiler',
                        'route' => 'permissions.index',
                        'active' => ['permissions.*'],
                        'permission' => 'user.view',
                    ],
                    [
                        'label' => 'Aktivite Kayıtları',
                        'route' => 'users.activity-log.index',
                        'active' => ['users.activity-log.*'],
                        'roles' => ['super_admin', 'general_manager'],
                    ],
                    [
                        'label' => 'Bildirimler',
                        'route' => 'notifications.index',
                        'active' => ['notifications.*'],
                        'permission' => 'notification.view',
                    ],
                    [
                        'label' => 'Form Builder',
                        'route' => 'form-builder.index',
                        'active' => ['form-builder.*'],
                        'permission' => 'form_builder.view',
                    ],
                    [
                        'label' => 'Landing Page Builder',
                        'route' => 'landing-page-builder.index',
                        'active' => ['landing-page-builder.*'],
                        'permission' => 'landing_page.view',
                    ],
                ],
            ],
        ]));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function canView(array $item): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        if (! empty($item['roles']) && ! $user->hasAnyRole($item['roles'])) {
            return false;
        }

        if (! empty($item['role']) && ! $user->hasRole($item['role'])) {
            return false;
        }

        if (! empty($item['except_roles']) && $user->hasAnyRole($item['except_roles'])) {
            return false;
        }

        if (! empty($item['permission']) && ! $user->can($item['permission'])) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, string>  $patterns
     */
    public static function isActive(array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, bool>
     */
    public static function initialExpandedGroups(): array
    {
        $expanded = [];

        foreach (self::items() as $item) {
            if (($item['type'] ?? '') !== 'group') {
                continue;
            }

            if (! self::canView($item)) {
                continue;
            }

            if (! empty($item['disabled'])) {
                $expanded[$item['key']] = false;

                continue;
            }

            $expanded[$item['key']] = self::isActive($item['active'] ?? []);
        }

        return $expanded;
    }
}
