<?php

namespace App\Modules\Courier\Exports;

use App\Core\Exports\ListExport;
use App\Modules\Courier\Data\CourierDummyData;
use App\Modules\Courier\Data\CourierEarningDummyData;

final class CourierListExportSheets
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function couriers(array $filters): array
    {
        $statusLabels = CourierDummyData::statuses();

        return ListExport::sheet(
            CourierDummyData::filter($filters),
            ['Ad Soyad', 'TC Kimlik No', 'Telefon', 'Kurye Tipi', 'Bağlı Acente', 'Araç Tipi', 'Aktif İşletme', 'Durum'],
            [
                fn (array $row) => $row['full_name'],
                fn (array $row) => $row['tc_number'],
                fn (array $row) => $row['phone'],
                fn (array $row) => $row['courier_type_label'],
                fn (array $row) => $row['agency_name'] ?? '—',
                fn (array $row) => $row['vehicle_type_label'],
                fn (array $row) => $row['active_business_name'] ?? '—',
                fn (array $row) => $statusLabels[$row['status']] ?? $row['status'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function earnings(array $filters): array
    {
        return ListExport::sheet(
            CourierEarningDummyData::filter($filters),
            ['Kurye', 'Kurye Tipi', 'İşletme', 'Dönem', 'Paket', 'Hakediş', 'Kesinti', 'Net Ödeme', 'Ödeme Durumu', 'Ödeme Tarihi'],
            [
                fn (array $row) => $row['courier_name'],
                fn (array $row) => $row['courier_type'],
                fn (array $row) => $row['business_name'],
                fn (array $row) => $row['period_label'],
                fn (array $row) => $row['package_count'],
                fn (array $row) => $row['earning_amount'],
                fn (array $row) => $row['deduction'],
                fn (array $row) => $row['net_payment'],
                fn (array $row) => $row['payment_status_label'] ?? $row['payment_status'],
                fn (array $row) => $row['payment_date_formatted'] ?? '—',
            ],
        );
    }
}
