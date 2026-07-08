<?php

namespace App\Modules\Agency\Exports;

use App\Core\Exports\ListExport;
use App\Modules\Agency\Data\AgencyActivityDummyData;
use App\Modules\Agency\Data\AgencyContactDummyData;
use App\Modules\Agency\Data\AgencyContractDummyData;
use App\Modules\Agency\Data\AgencyCourierDummyData;
use App\Modules\Agency\Data\AgencyDummyData;
use App\Modules\Agency\Data\AgencyEarningDummyData;

final class AgencyListExportSheets
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function agencies(array $filters): array
    {
        $statusLabels = AgencyDummyData::statuses();

        return ListExport::sheet(
            AgencyDummyData::filter($filters),
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
        return ListExport::sheet(
            AgencyContactDummyData::filter($filters),
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
        return ListExport::sheet(
            AgencyContractDummyData::filter($filters),
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
        $statusLabels = [
            'active' => 'Aktif',
            'inactive' => 'Pasif',
            'on_leave' => 'İzinli',
        ];

        return ListExport::sheet(
            AgencyCourierDummyData::filter($filters),
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
        return ListExport::sheet(
            AgencyEarningDummyData::filter($filters),
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
                fn (array $row) => $row['payment_status_label'] ?? $row['payment_status'],
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
