<?php

namespace App\Modules\Notification\Data;

class NotificationFormData
{
    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            'earning_created' => 'Hakediş Oluşturuldu',
            'earning_approved' => 'Hakediş Onaylandı',
            'user_created' => 'Kullanıcı Oluşturuldu',
            'finance_synced' => 'Finans Kaydı Oluşturuldu',
            'system' => 'Sistem Bildirimi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function modules(): array
    {
        return [
            'earnings' => 'Hakedişler',
            'users' => 'Kullanıcılar',
            'finance' => 'Finans',
            'system' => 'Sistem',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'all' => 'Tümü',
            'unread' => 'Okunmamış',
            'read' => 'Okunmuş',
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
        ];
    }

    public static function typeLabel(string $type): string
    {
        return self::types()[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    public static function moduleLabel(string $module): string
    {
        return self::modules()[$module] ?? ucfirst(str_replace('_', ' ', $module));
    }

    public static function moduleForType(string $type): string
    {
        return match ($type) {
            'earning_created', 'earning_approved' => 'earnings',
            'user_created' => 'users',
            'finance_synced' => 'finance',
            default => 'system',
        };
    }

    public static function settingKeyForType(string $type): ?string
    {
        return match ($type) {
            'earning_created', 'earning_approved', 'finance_synced' => 'earning_notifications',
            'user_created' => 'system_notifications',
            default => 'system_notifications',
        };
    }
}
