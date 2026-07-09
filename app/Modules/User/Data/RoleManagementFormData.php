<?php

namespace App\Modules\User\Data;

class RoleManagementFormData
{
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
     * Spatie role slug => görünen ad ve UI meta verileri.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            'super_admin' => [
                'display_name' => 'Süper Admin',
                'description' => 'Sistemin tüm modüllerine ve ayarlarına tam erişim. Silinemez.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => false,
                'icon' => 'shield-check',
                'color' => 'rose',
            ],
            'general_manager' => [
                'display_name' => 'Genel Müdür',
                'description' => 'Finans, operasyon ve raporlama modüllerine üst düzey erişim.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'briefcase',
                'color' => 'violet',
            ],
            'operations_manager' => [
                'display_name' => 'Operasyon Yöneticisi',
                'description' => 'İşletme, kurye, acente ve atama süreçlerini yönetir.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'cog',
                'color' => 'blue',
            ],
            'finance_officer' => [
                'display_name' => 'Finans Sorumlusu',
                'description' => 'Gelir, gider, tahsilat ve finansal raporlara erişim.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'currency',
                'color' => 'emerald',
            ],
            'operations_staff' => [
                'display_name' => 'Operasyon Personeli',
                'description' => 'Operasyon modüllerinde görüntüleme ve temel işlem yetkileri.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'users',
                'color' => 'cyan',
            ],
            'business' => [
                'display_name' => 'İşletme',
                'description' => 'İşletme kullanıcıları için kendi verilerine erişim.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'building',
                'color' => 'amber',
            ],
            'courier' => [
                'display_name' => 'Kurye',
                'description' => 'Kurye kullanıcıları için hakediş ve sözleşme görüntüleme.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'truck',
                'color' => 'indigo',
            ],
            'agency' => [
                'display_name' => 'Acente',
                'description' => 'Acente kullanıcıları için kurye ve hakediş takibi.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'office',
                'color' => 'teal',
            ],
            'regional_coordinator' => [
                'display_name' => 'Bölge Koordinatörü',
                'description' => 'Özel oluşturulmuş rol. Bölgesel operasyon koordinasyonu.',
                'status' => 'active',
                'is_system' => false,
                'is_deletable' => true,
                'can_deactivate' => true,
                'icon' => 'map',
                'color' => 'primary',
            ],
            'reporting_analyst' => [
                'display_name' => 'Raporlama Analisti',
                'description' => 'Özel oluşturulmuş rol. Finansal raporlama ve analiz.',
                'status' => 'inactive',
                'is_system' => false,
                'is_deletable' => true,
                'can_deactivate' => true,
                'icon' => 'chart',
                'color' => 'slate',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function meta(string $name): array
    {
        $definitions = self::definitions();

        if (isset($definitions[$name])) {
            return $definitions[$name];
        }

        return [
            'display_name' => ucwords(str_replace('_', ' ', $name)),
            'description' => 'Özel oluşturulmuş rol.',
            'status' => 'active',
            'is_system' => false,
            'is_deletable' => true,
            'can_deactivate' => true,
            'icon' => 'shield',
            'color' => 'gray',
        ];
    }
}
