<?php

namespace App\Modules\User\Data;

use App\Modules\User\Models\RoleProfile;

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
            'sales_manager' => [
                'display_name' => 'Satış Müdürü',
                'description' => 'İşletme, sözleşme ve form başvuru satış süreçlerini yönetir.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'chart',
                'color' => 'amber',
            ],
            'operations_specialist' => [
                'display_name' => 'Operasyon Uzmanı',
                'description' => 'İşletme, kurye, acente, atama ve form başvuru süreçlerini yönetir.',
                'status' => 'active',
                'is_system' => true,
                'is_deletable' => false,
                'can_deactivate' => true,
                'icon' => 'cog',
                'color' => 'blue',
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function meta(string $name): array
    {
        $definitions = self::definitions();
        $base = $definitions[$name] ?? [
            'display_name' => ucwords(str_replace('_', ' ', $name)),
            'description' => 'Özel oluşturulmuş rol.',
            'status' => 'active',
            'is_system' => false,
            'is_deletable' => true,
            'can_deactivate' => true,
            'icon' => 'shield',
            'color' => 'gray',
        ];

        $profile = RoleProfile::query()->where('role_name', $name)->first();

        if ($profile === null) {
            return $base;
        }

        return array_merge($base, [
            'display_name' => $profile->display_name,
            'description' => $profile->description ?? $base['description'],
            'status' => $profile->status,
            'is_system' => $profile->is_system,
            'is_deletable' => $profile->is_system ? $base['is_deletable'] : true,
        ]);
    }
}
