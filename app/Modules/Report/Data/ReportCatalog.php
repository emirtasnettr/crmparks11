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
                'key' => 'earnings',
                'title' => 'Hakediş Özeti',
                'description' => 'Dönem bazlı hakediş gelir, gider ve kâr özeti.',
                'icon' => 'earning',
                'route' => 'reports.earnings',
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
            ],
            [
                'key' => 'courier_performance',
                'title' => 'Kurye Performansı',
                'description' => 'Kurye bazlı paket, hakediş ve kâr özeti.',
                'icon' => 'courier',
                'route' => 'reports.courier-performance',
            ],
            [
                'key' => 'agency_share',
                'title' => 'Acente Payı',
                'description' => 'Acente bazlı komisyon ve ödeme özeti.',
                'icon' => 'agency',
                'route' => 'reports.agency-share',
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
                if (! empty($report['permission']) && ! $user?->can($report['permission'])) {
                    return false;
                }

                return $user?->can('report.view') ?? false;
            })
            ->values()
            ->all();
    }
}
