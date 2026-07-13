<?php

namespace App\Modules\Report\Data;

class ReportCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'business_pipeline',
                'title' => 'İşletme Pipeline',
                'description' => 'İşletmelerin durum bazlı dağılımı ve satış hunisi.',
                'icon' => 'building',
                'route' => 'reports.business-pipeline',
                'permission' => 'business.view',
            ],
            [
                'key' => 'opening_stage',
                'title' => 'Açılış Aşaması',
                'description' => 'Açılış aşamasındaki işletmeler ve geciken açılışlar.',
                'icon' => 'clock',
                'route' => 'reports.opening-stage',
                'permission' => 'business.view',
            ],
            [
                'key' => 'contract_expiry',
                'title' => 'Sözleşme Vadeleri',
                'description' => 'Yakında bitecek ve gecikmiş işletme sözleşmeleri.',
                'icon' => 'contract',
                'route' => 'reports.contract-expiry',
                'permission' => 'business.view',
            ],
            [
                'key' => 'earnings',
                'title' => 'Hakediş Özeti',
                'description' => 'Dönem bazlı hakediş gelir, gider ve kâr özeti.',
                'icon' => 'earning',
                'route' => 'reports.earnings',
                'permission' => 'earning.view',
            ],
            [
                'key' => 'collections',
                'title' => 'Tahsilat Yaşlandırma',
                'description' => 'Bekleyen ve geciken tahsilatların vade analizi.',
                'icon' => 'chart',
                'route' => 'reports.collections',
                'permission' => 'dashboard.financial',
            ],
            [
                'key' => 'operations',
                'title' => 'Operasyon Özeti',
                'description' => 'İşletme, kurye, acente ve atama sayıları.',
                'icon' => 'report',
                'route' => 'reports.operations',
                'permission' => 'courier.view',
            ],
            [
                'key' => 'courier_performance',
                'title' => 'Kurye Performansı',
                'description' => 'Kurye bazlı paket, hakediş ve kâr özeti.',
                'icon' => 'courier',
                'route' => 'reports.courier-performance',
                'permission' => 'courier.view',
            ],
            [
                'key' => 'agency_share',
                'title' => 'Acente Payı',
                'description' => 'Acente bazlı komisyon ve ödeme özeti.',
                'icon' => 'agency',
                'route' => 'reports.agency-share',
                'permission' => 'agency.view',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forUser($user): array
    {
        return collect(self::all())
            ->filter(function (array $report) use ($user): bool {
                if (! ($user?->can('report.view') ?? false)) {
                    return false;
                }

                if (! empty($report['permission']) && ! $user->can($report['permission'])) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }
}
