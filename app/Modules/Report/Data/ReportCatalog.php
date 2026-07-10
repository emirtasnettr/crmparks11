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
