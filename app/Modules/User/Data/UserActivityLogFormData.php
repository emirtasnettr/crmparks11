<?php

namespace App\Modules\User\Data;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceInvoice;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserActivityLogFormData
{
    /**
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
            'user_deleted' => 'Kullanıcı Pasife Alındı',
            'role_created' => 'Rol Oluşturuldu',
            'role_updated' => 'Rol Güncellendi',
            'role_deleted' => 'Rol Silindi',
            'role_changed' => 'Rol Değiştirildi',
            'permission_updated' => 'Yetki Güncellendi',
            'password_changed' => 'Şifre Değiştirildi',
            'password_reset_sent' => 'Şifre Sıfırlama Gönderildi',
            'business_created' => 'İşletme Oluşturuldu',
            'courier_created' => 'Kurye Oluşturuldu',
            'courier_updated' => 'Kurye Güncellendi',
            'courier_activated' => 'Kurye Aktifleştirildi',
            'courier_deactivated' => 'Kurye Pasifleştirildi',
            'earning_created' => 'Hakediş Oluşturuldu',
            'revenue_created' => 'Gelir Kaydı Oluşturuldu',
            'expense_created' => 'Gider Kaydı Oluşturuldu',
            'collection_created' => 'Tahsilat Yapıldı',
            'payment_created' => 'Ödeme Yapıldı',
            'invoice_created' => 'Fatura Kesildi',
            'settings_updated' => 'Ayarlar Güncellendi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function roles(): array
    {
        return UserManagementFormData::roleLabels();
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

    public static function activityLabel(string $action): string
    {
        return self::activityTypes()[$action]
            ?? ucfirst(str_replace('_', ' ', $action));
    }

    public static function moduleForAction(string $action): ?string
    {
        $map = [
            'login' => 'auth',
            'logout' => 'auth',
            'login_failed' => 'auth',
            'password_changed' => 'auth',
            'password_reset_sent' => 'auth',
            'user_created' => 'users',
            'user_updated' => 'users',
            'user_deleted' => 'users',
            'role_created' => 'roles',
            'role_updated' => 'roles',
            'role_deleted' => 'roles',
            'role_changed' => 'roles',
            'permission_updated' => 'permissions',
            'business_created' => 'businesses',
            'courier_created' => 'couriers',
            'courier_updated' => 'couriers',
            'courier_activated' => 'couriers',
            'courier_deactivated' => 'couriers',
            'earning_created' => 'earnings',
            'revenue_created' => 'earnings',
            'expense_created' => 'finance',
            'collection_created' => 'collections',
            'payment_created' => 'payments',
            'invoice_created' => 'invoices',
            'settings_updated' => 'settings',
        ];

        if (isset($map[$action])) {
            return $map[$action];
        }

        return match (true) {
            str_starts_with($action, 'user_') => 'users',
            str_starts_with($action, 'role_') => 'roles',
            str_starts_with($action, 'permission_') => 'permissions',
            str_starts_with($action, 'business_') => 'businesses',
            str_starts_with($action, 'courier_') => 'couriers',
            str_starts_with($action, 'agency_') => 'agencies',
            str_starts_with($action, 'contract_') => 'contracts',
            str_starts_with($action, 'revenue_'), str_starts_with($action, 'earning_') => 'earnings',
            str_starts_with($action, 'collection_') => 'collections',
            str_starts_with($action, 'payment_') => 'payments',
            str_starts_with($action, 'invoice_') => 'invoices',
            str_starts_with($action, 'expense_'), str_starts_with($action, 'current_account') => 'finance',
            str_starts_with($action, 'setting') => 'settings',
            default => null,
        };
    }

    public static function moduleForSubjectType(?string $subjectType): ?string
    {
        return match ($subjectType) {
            User::class => 'users',
            Role::class => 'roles',
            Permission::class => 'permissions',
            Business::class => 'businesses',
            Courier::class => 'couriers',
            Agency::class => 'agencies',
            FinanceRevenue::class => 'earnings',
            FinanceCollection::class => 'collections',
            FinancePayment::class => 'payments',
            FinanceInvoice::class => 'invoices',
            FinanceExpense::class => 'finance',
            default => null,
        };
    }

    public static function resolveModule(string $action, ?string $subjectType): string
    {
        return self::moduleForAction($action)
            ?? self::moduleForSubjectType($subjectType)
            ?? 'finance';
    }

    public static function resolveStatus(string $action, ?array $newValues = null): string
    {
        return match (true) {
            $action === 'login_failed' => 'failed',
            in_array($action, ['role_changed', 'permission_updated', 'settings_updated', 'courier_deactivated'], true) => 'warning',
            in_array($newValues['status'] ?? null, ['cancelled', 'deleted', 'inactive'], true) => 'warning',
            ($newValues['error'] ?? false) === true => 'failed',
            default => 'success',
        };
    }
}
