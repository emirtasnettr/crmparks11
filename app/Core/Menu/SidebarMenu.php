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
        return [
            [
                'key' => 'dashboard',
                'type' => 'link',
                'label' => 'Dashboard',
                'icon' => 'chart',
                'route' => 'dashboard',
                'active' => ['dashboard'],
                'permission' => 'dashboard.view',
            ],
            [
                'key' => 'businesses',
                'type' => 'group',
                'label' => 'İşletmeler',
                'icon' => 'building',
                'permission' => 'business.view',
                'active' => ['businesses.*'],
                'children' => array_values(array_filter([
                    ['label' => 'İşletmeler', 'route' => 'businesses.index', 'active' => ['businesses.index', 'businesses.create']],
                    ['label' => 'Yetkililer', 'route' => 'businesses.contacts.index', 'active' => ['businesses.contacts.*']],
                    ['label' => 'Sözleşmeler', 'route' => 'businesses.contracts.index', 'active' => ['businesses.contracts.*']],
                    ['label' => 'Atanan Kuryeler', 'route' => 'businesses.assignments.index', 'active' => ['businesses.assignments.*']],
                    BusinessFeatures::earningsEnabled()
                        ? ['label' => 'Hakedişler', 'route' => 'businesses.earnings.index', 'active' => ['businesses.earnings.*']]
                        : null,
                    ['label' => 'Evraklar', 'route' => 'businesses.documents.index', 'active' => ['businesses.documents.*']],
                    ['label' => 'Hareket Geçmişi', 'route' => 'businesses.activities.index', 'active' => ['businesses.activities.*']],
                ])),
            ],
            [
                'key' => 'couriers',
                'type' => 'group',
                'label' => 'Kuryeler',
                'icon' => 'courier',
                'permission' => 'courier.view',
                'active' => ['couriers.*'],
                'children' => array_values(array_filter([
                    ['label' => 'Kuryeler', 'route' => 'couriers.index', 'active' => ['couriers.index', 'couriers.create']],
                    ['label' => 'Belgeler', 'route' => 'couriers.documents.index', 'active' => ['couriers.documents.*']],
                    CourierFeatures::earningsEnabled()
                        ? ['label' => 'Hakedişler', 'route' => 'couriers.earnings.index', 'active' => ['couriers.earnings.*']]
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
                'icon' => 'agency',
                'permission' => 'agency.view',
                'active' => ['agencies.*'],
                'children' => array_values(array_filter([
                    ['label' => 'Acenteler', 'route' => 'agencies.index', 'active' => ['agencies.index', 'agencies.create']],
                    ['label' => 'Yetkililer', 'route' => 'agencies.contacts.index', 'active' => ['agencies.contacts.*']],
                    ['label' => 'Kuryeler', 'route' => 'agencies.couriers.index', 'active' => ['agencies.couriers.*']],
                    ['label' => 'Sözleşmeler', 'route' => 'agencies.contracts.index', 'active' => ['agencies.contracts.*']],
                    AgencyFeatures::earningsEnabled()
                        ? ['label' => 'Hakedişler', 'route' => 'agencies.earnings.index', 'active' => ['agencies.earnings.*']]
                        : null,
                    ['label' => 'Evraklar', 'route' => 'agencies.documents.index', 'active' => ['agencies.documents.*']],
                    ['label' => 'Hareket Geçmişi', 'route' => 'agencies.activities.index', 'active' => ['agencies.activities.*']],
                ])),
            ],
            [
                'key' => 'finance',
                'type' => 'group',
                'label' => 'Finans',
                'icon' => 'earning',
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
                'key' => 'users',
                'type' => 'group',
                'label' => 'Kullanıcı Yönetimi',
                'icon' => 'users',
                'permission' => 'user.view',
                'active' => ['users.*', 'roles.*', 'permissions.*'],
                'children' => [
                    ['label' => 'Kullanıcılar', 'route' => 'users.index', 'active' => ['users.index', 'users.show']],
                    ['label' => 'Roller', 'route' => 'roles.index', 'active' => ['roles.*']],
                    ['label' => 'Yetkiler', 'route' => 'permissions.index', 'active' => ['permissions.*']],
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
                ],
            ],
            [
                'key' => 'settings',
                'type' => 'link',
                'label' => 'Sistem Ayarları',
                'icon' => 'settings',
                'route' => 'settings.index',
                'active' => ['settings.*'],
                'roles' => ['super_admin'],
            ],
            [
                'key' => 'form-builder',
                'type' => 'link',
                'label' => 'Form Builder',
                'icon' => 'form-builder',
                'route' => 'form-builder.index',
                'active' => ['form-builder.*'],
                'permission' => 'form_builder.view',
            ],
            [
                'key' => 'landing-page-builder',
                'type' => 'link',
                'label' => 'Landing Page Builder',
                'icon' => 'landing-page',
                'route' => 'landing-page-builder.index',
                'active' => ['landing-page-builder.*'],
                'permission' => 'landing_page.view',
            ],
        ];
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
