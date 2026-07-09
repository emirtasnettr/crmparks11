<?php

namespace App\Modules\User\Exports;

use App\Core\Exports\ListExport;
use App\Modules\User\Services\UserActivityLogService;
use App\Modules\User\Services\UserManagementService;

final class UserListExportSheets
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function users(array $filters): array
    {
        return ListExport::sheet(
            app(UserManagementService::class)->exportRows($filters),
            ['Ad Soyad', 'E-Posta', 'Telefon', 'Rol', 'Bağlı Birim', 'Son Giriş', 'Durum'],
            [
                fn (array $row) => $row['full_name'],
                fn (array $row) => $row['email'],
                fn (array $row) => $row['phone'],
                fn (array $row) => implode(', ', $row['role_labels']),
                fn (array $row) => $row['linked_unit'] ?? '—',
                fn (array $row) => $row['last_login_formatted'] ?? '—',
                fn (array $row) => $row['status_label'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function activityLog(array $filters): array
    {
        return ListExport::sheet(
            app(UserActivityLogService::class)->exportRows($filters),
            ['Tarih', 'Saat', 'Kullanıcı', 'Rol', 'Modül', 'Aktivite', 'IP Adresi', 'Tarayıcı', 'Durum'],
            [
                fn (array $row) => $row['date_formatted'],
                fn (array $row) => $row['time_formatted'],
                fn (array $row) => $row['user_name'],
                fn (array $row) => $row['role_label'],
                fn (array $row) => $row['module_label'],
                fn (array $row) => $row['activity_type_label'],
                fn (array $row) => $row['ip_address'],
                fn (array $row) => $row['browser'],
                fn (array $row) => $row['status_label'],
            ],
        );
    }
}
