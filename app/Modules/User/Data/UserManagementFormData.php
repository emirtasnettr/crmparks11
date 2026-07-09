<?php

namespace App\Modules\User\Data;

class UserManagementFormData
{
    /**
     * Spatie Permission role slug => görünen ad.
     *
     * @return array<string, string>
     */
    public static function roleLabels(): array
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
}
