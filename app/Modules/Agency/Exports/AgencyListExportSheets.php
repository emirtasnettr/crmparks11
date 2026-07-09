<?php

namespace App\Modules\Agency\Exports;

use App\Core\Exports\ListExport;
use App\Modules\Agency\Data\AgencyActivityDummyData;
use App\Modules\Agency\Data\AgencyCourierFormData;
use App\Modules\Agency\Data\AgencyFormData;
use App\Modules\Agency\Services\AgencyContactPresenter;
use App\Modules\Agency\Services\AgencyContactService;
use App\Modules\Agency\Services\AgencyCourierService;
use App\Modules\Agency\Services\AgencyPresenter;
use App\Modules\Agency\Services\AgencyService;

final class AgencyListExportSheets
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function agencies(array $filters): array
    {
        $statusLabels = AgencyFormData::statuses();
        $service = app(AgencyService::class);
        $presenter = app(AgencyPresenter::class);

        $rows = $service->filter($filters)
            ->map(fn ($agency) => $presenter->indexRow($agency))
            ->all();

        return ListExport::sheet(
            $rows,
            ['Firma Ünvanı', 'Vergi No', 'Yetkili', 'Telefon', 'İl / İlçe', 'Aktif Kurye', 'Aktif İşletme', 'Durum'],
            [
                fn (array $row) => $row['company_name'],
                fn (array $row) => $row['tax_number'],
                fn (array $row) => $row['authorized_person'],
                fn (array $row) => $row['phone'],
                fn (array $row) => $row['location'] ?? ($row['city'].' / '.$row['district']),
                fn (array $row) => $row['active_couriers'],
                fn (array $row) => $row['active_businesses'],
                fn (array $row) => $statusLabels[$row['status']] ?? $row['status'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function contacts(array $filters): array
    {
        $service = app(AgencyContactService::class);
        $presenter = app(AgencyContactPresenter::class);

        return ListExport::sheet(
            $service->filter($filters)
                ->map(fn ($contact) => $presenter->indexRow($contact))
                ->values()
                ->all(),
            ['Acente', 'Ad Soyad', 'Görevi', 'Telefon', 'E-Posta', 'Varsayılan', 'Durum'],
            [
                fn (array $row) => $row['agency_name'],
                fn (array $row) => $row['full_name'],
                fn (array $row) => $row['title'],
                fn (array $row) => $row['phone'],
                fn (array $row) => $row['email'],
                fn (array $row) => ListExport::yesNo($row['is_default']),
                fn (array $row) => $row['status_label'] ?? $row['status'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function contracts(array $filters): array
    {
        $service = app(\App\Modules\Agency\Services\AgencyContractService::class);
        $presenter = app(\App\Modules\Agency\Services\AgencyContractPresenter::class);

        $rows = $service->filter($filters)
            ->map(fn ($contract) => $presenter->indexRow($contract))
            ->all();

        return ListExport::sheet(
            $rows,
            ['Acente', 'Sözleşme No', 'Sözleşme Türü', 'Başlangıç', 'Bitiş', 'Kalan Gün', 'Durum'],
            [
                fn (array $row) => $row['agency_name'],
                fn (array $row) => $row['contract_number'],
                fn (array $row) => $row['contract_type_label'],
                fn (array $row) => $row['start_date_formatted'],
                fn (array $row) => $row['end_date_formatted'] ?? '—',
                fn (array $row) => $row['remaining_days'] ?? '—',
                fn (array $row) => $row['status'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function couriers(array $filters): array
    {
        $service = app(AgencyCourierService::class);
        $statusLabels = AgencyCourierFormData::statuses();

        return ListExport::sheet(
            $service->filter($filters)->values()->all(),
            ['Kurye', 'Acente', 'Telefon', 'Araç Tipi', 'Aktif İşletme', 'Katılış Tarihi', 'Durum'],
            [
                fn (array $row) => $row['courier_name'],
                fn (array $row) => $row['agency_name'],
                fn (array $row) => $row['phone'],
                fn (array $row) => $row['vehicle_type_label'],
                fn (array $row) => $row['active_business_name'] ?? '—',
                fn (array $row) => $row['join_date_formatted'],
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
        $service = app(\App\Modules\Agency\Services\AgencyEarningService::class);
        $paymentLabels = \App\Modules\Agency\Data\AgencyEarningFormData::paymentStatuses();
        $rows = $service->filter($filters)->values()->all();

        return ListExport::sheet(
            $rows,
            ['Acente', 'Referans', 'Dönem', 'Dönem Tipi', 'Kurye Sayısı', 'Paket', 'Hakediş', 'Kesinti', 'Net Ödeme', 'Ödeme Durumu', 'Ödeme Tarihi'],
            [
                fn (array $row) => $row['agency_name'],
                fn (array $row) => $row['reference'],
                fn (array $row) => $row['period_label'],
                fn (array $row) => $row['period_type_label'],
                fn (array $row) => $row['courier_count'],
                fn (array $row) => $row['package_count'],
                fn (array $row) => $row['gross_amount'],
                fn (array $row) => $row['deduction'],
                fn (array $row) => $row['net_payment'],
                fn (array $row) => $paymentLabels[$row['payment_status']] ?? $row['payment_status'],
                fn (array $row) => $row['payment_date_formatted'] ?? '—',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function activities(array $filters): array
    {
        return ListExport::sheet(
            AgencyActivityDummyData::filter($filters),
            ['Tarih', 'Saat', 'Acente', 'İşlem Türü', 'İşlemi Yapan', 'IP Adresi', 'Açıklama'],
            [
                fn (array $row) => $row['occurred_at_date'],
                fn (array $row) => $row['occurred_at_time'],
                fn (array $row) => $row['agency_name'],
                fn (array $row) => $row['action_label'],
                fn (array $row) => $row['user_name'],
                fn (array $row) => $row['ip_address'],
                fn (array $row) => $row['description'],
            ],
        );
    }
}
