<?php

namespace App\Modules\Business\Exports;

use App\Core\Exports\ListExport;
use App\Modules\Business\Data\BusinessAssignmentDummyData;
use App\Modules\Business\Data\BusinessContactDummyData;
use App\Modules\Business\Data\BusinessContractDummyData;
use App\Modules\Business\Data\BusinessFormData;
use App\Modules\Business\Services\BusinessPresenter;
use App\Modules\Business\Services\BusinessService;

final class BusinessListExportSheets
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function businesses(array $filters): array
    {
        $pricingLabels = BusinessFormData::pricingModels() + ['fixed' => 'Sabit Ücret'];
        $statusLabels = BusinessFormData::statuses();
        $service = app(BusinessService::class);
        $presenter = app(BusinessPresenter::class);

        $items = $service->filter($filters)
            ->map(fn ($business) => $presenter->indexRow($business))
            ->all();

        return ListExport::sheet(
            $items,
            [
                'Firma Ünvanı',
                'Marka Adı',
                'İşletmeden Alınan Ücret',
                'Kuryeye Verilen Ücret',
                'Telefon',
                'İl',
                'İlçe',
                'Çalışma Modeli',
                'Aktif Kurye',
                'Durum',
            ],
            [
                fn (array $row) => $row['company_name'],
                fn (array $row) => $row['brand_name'],
                fn (array $row) => $row['customer_price_label'],
                fn (array $row) => $row['courier_price_label'],
                fn (array $row) => $row['phone'],
                fn (array $row) => $row['city'],
                fn (array $row) => $row['district'],
                fn (array $row) => $pricingLabels[$row['pricing_model']] ?? $row['pricing_model'],
                fn (array $row) => $row['active_couriers'],
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
            BusinessContactDummyData::filter($filters),
            ['İşletme', 'Ad Soyad', 'Görevi', 'Telefon', 'E-Posta', 'Varsayılan Yetkili', 'Durum'],
            [
                fn (array $row) => $row['business_name'],
                fn (array $row) => $row['full_name'],
                fn (array $row) => $row['title'],
                fn (array $row) => $row['phone'],
                fn (array $row) => $row['email'],
                fn (array $row) => ListExport::yesNo($row['is_default']),
                fn (array $row) => $row['status'] === 'active' ? 'Aktif' : 'Pasif',
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
            BusinessContractDummyData::filter($filters),
            ['İşletme', 'Sözleşme No', 'Sözleşme Türü', 'Başlangıç Tarihi', 'Bitiş Tarihi', 'Kalan Gün', 'Durum'],
            [
                fn (array $row) => $row['business_name'],
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
    public static function assignments(array $filters): array
    {
        $workStatusLabels = [
            'active' => 'Aktif',
            'left' => 'Ayrıldı',
            'on_leave' => 'İzinli',
        ];

        return ListExport::sheet(
            BusinessAssignmentDummyData::filter($filters),
            ['Kurye', 'Telefon', 'İşletme', 'Acente', 'Kurye Tipi', 'Başlangıç', 'Bitiş', 'Çalışma Durumu'],
            [
                fn (array $row) => $row['courier_name'],
                fn (array $row) => $row['courier_phone'],
                fn (array $row) => $row['business_name'],
                fn (array $row) => $row['agency_name'] ?? '—',
                fn (array $row) => $row['courier_type_label'],
                fn (array $row) => $row['start_date_formatted'],
                fn (array $row) => $row['end_date_formatted'] ?? '—',
                fn (array $row) => $workStatusLabels[$row['work_status']] ?? $row['work_status'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function earnings(array $filters): array
    {
        $pricingLabels = BusinessFormData::pricingModels() + ['fixed' => 'Sabit Ücret'];

        return ListExport::sheet(
            BusinessEarningDummyData::filter($filters),
            ['İşletme', 'Kurye', 'Dönem', 'Çalışma Modeli', 'Paket', 'İşletmeden Gelir', 'Kurye Ödemesi', 'Kâr', 'Durum'],
            [
                fn (array $row) => $row['business_name'],
                fn (array $row) => $row['courier_name'],
                fn (array $row) => $row['period_label'],
                fn (array $row) => $pricingLabels[$row['pricing_model']] ?? $row['pricing_model'],
                fn (array $row) => $row['package_count'],
                fn (array $row) => $row['revenue'],
                fn (array $row) => $row['courier_payment'],
                fn (array $row) => $row['profit'],
                fn (array $row) => $row['status_label'] ?? $row['status'],
            ],
        );
    }
}
