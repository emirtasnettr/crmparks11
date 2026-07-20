<?php

namespace App\Modules\Courier\Exports;

use App\Core\Exports\ListExport;
use App\Modules\Courier\Data\CourierFormData;
use App\Modules\Courier\Services\CourierPresenter;
use App\Modules\Courier\Services\CourierService;

final class CourierListExportSheets
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function couriers(array $filters): array
    {
        $statusLabels = CourierFormData::statuses();
        $service = app(CourierService::class);
        $presenter = app(CourierPresenter::class);

        $rows = $service->filter($filters)
            ->map(fn ($courier) => $presenter->indexRow($courier))
            ->all();

        return ListExport::sheet(
            $rows,
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
        $service = app(\App\Modules\Courier\Services\CourierEarningService::class);
        $presenter = app(\App\Modules\Courier\Services\CourierEarningPresenter::class);
        $paymentLabels = \App\Modules\Courier\Data\CourierEarningFormData::paymentStatuses();

        $rows = $service->filter($filters)
            ->map(fn ($line) => $presenter->indexRow($line))
            ->all();

        return ListExport::sheet(
            $rows,
            ['Kurye', 'Kurye Tipi', 'İşletme', 'Dönem', 'Paket', 'Saat', 'Hakediş', 'Kesinti', 'Net Ödeme', 'Ödeme Durumu', 'Ödeme Tarihi'],
            [
                fn (array $row) => $row['courier_name'],
                fn (array $row) => $row['courier_type'],
                fn (array $row) => $row['business_name'],
                fn (array $row) => $row['period_label'],
                fn (array $row) => $row['package_count'],
                fn (array $row) => $row['worked_hours'],
                fn (array $row) => $row['earning_amount'],
                fn (array $row) => $row['deduction'],
                fn (array $row) => $row['net_payment'],
                fn (array $row) => $paymentLabels[$row['payment_status']] ?? $row['payment_status'],
                fn (array $row) => $row['payment_date_formatted'] ?? '—',
            ],
        );
    }
}
