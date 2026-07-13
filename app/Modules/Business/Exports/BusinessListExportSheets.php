<?php

namespace App\Modules\Business\Exports;

use App\Core\Exports\ListExport;
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

        $canViewCustomerPricing = \App\Modules\Business\Support\BusinessPricingVisibility::canViewCustomerAndNetPricing();

        $headings = [
            'Marka Adı',
            'Firma Ünvanı',
        ];
        $columns = [
            fn (array $row) => $row['display_name'] ?? $row['brand_name'],
            fn (array $row) => $row['company_name'],
        ];

        if ($canViewCustomerPricing) {
            $headings[] = 'İşletmeden Alınan Ücret';
            $columns[] = fn (array $row) => $row['customer_price_label'];
        }

        $headings = array_merge($headings, [
            'Kuryeye Verilen Ücret',
            'Telefon',
            'İl',
            'İlçe',
            'Çalışma Modeli',
            'Aktif Kurye',
            'Durum',
        ]);
        $columns = array_merge($columns, [
            fn (array $row) => $row['courier_price_label'],
            fn (array $row) => $row['phone'],
            fn (array $row) => $row['city'],
            fn (array $row) => $row['district'],
            fn (array $row) => $pricingLabels[$row['pricing_model']] ?? $row['pricing_model'],
            fn (array $row) => $row['active_couriers'],
            fn (array $row) => $statusLabels[$row['status']] ?? $row['status'],
        ]);

        return ListExport::sheet($items, $headings, $columns);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public static function contacts(array $filters): array
    {
        $service = app(\App\Modules\Business\Services\BusinessContactService::class);
        $presenter = app(\App\Modules\Business\Services\BusinessContactPresenter::class);

        $rows = $service->filter($filters)
            ->map(fn ($contact) => $presenter->indexRow($contact))
            ->all();

        return ListExport::sheet(
            $rows,
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
        $service = app(\App\Modules\Business\Services\BusinessContractService::class);
        $presenter = app(\App\Modules\Business\Services\BusinessContractPresenter::class);

        $rows = $service->filter($filters)
            ->map(fn ($contract) => $presenter->indexRow($contract))
            ->all();

        return ListExport::sheet(
            $rows,
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
            'leaving_soon' => 'Yakında Ayrılacak',
            'on_leave' => 'İzinli',
        ];

        $service = app(\App\Modules\Business\Services\BusinessAssignmentService::class);
        $presenter = app(\App\Modules\Business\Services\BusinessAssignmentPresenter::class);

        $rows = $service->filter($filters)
            ->map(fn ($assignment) => $presenter->indexRow($assignment))
            ->all();

        return ListExport::sheet(
            $rows,
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
        $service = app(\App\Modules\Business\Services\BusinessEarningService::class);
        $presenter = app(\App\Modules\Business\Services\BusinessEarningPresenter::class);

        $rows = $service->filter($filters)
            ->map(fn ($line) => $presenter->indexRow($line))
            ->all();

        return ListExport::sheet(
            $rows,
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
