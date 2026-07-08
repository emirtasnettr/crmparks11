<?php

namespace App\Modules\Agency\Data;

use App\Support\DemoData;
use Carbon\Carbon;

class AgencyActivityDummyData
{
    /**
     * @return array<string, string>
     */
    public static function actionTypes(): array
    {
        return [
            'agency_created' => 'Acente Oluşturuldu',
            'agency_updated' => 'Acente Güncellendi',
            'contact_added' => 'Yetkili Eklendi',
            'contact_updated' => 'Yetkili Güncellendi',
            'courier_assigned' => 'Kurye Atandı',
            'courier_removed' => 'Kurye Ayrıldı',
            'earning_created' => 'Hakediş Oluşturuldu',
            'earning_updated' => 'Hakediş Güncellendi',
            'document_uploaded' => 'Evrak Yüklendi',
            'document_updated' => 'Evrak Güncellendi',
            'contract_created' => 'Sözleşme Oluşturuldu',
            'contract_updated' => 'Sözleşme Güncellendi',
            'contract_renewed' => 'Sözleşme Yenilendi',
            'agency_deactivated' => 'Acente Pasife Alındı',
            'agency_activated' => 'Acente Aktifleştirildi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dateRanges(): array
    {
        return [
            'today' => 'Bugün',
            'this_week' => 'Bu Hafta',
            'this_month' => 'Bu Ay',
            'last_7_days' => 'Son 7 Gün',
            'last_30_days' => 'Son 30 Gün',
            'this_year' => 'Bu Yıl',
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function users(): array
    {
        return [
            ['id' => 1, 'name' => 'Ahmet Yılmaz'],
            ['id' => 2, 'name' => 'Elif Demir'],
            ['id' => 3, 'name' => 'Mehmet Kaya'],
            ['id' => 4, 'name' => 'Zeynep Arslan'],
            ['id' => 5, 'name' => 'Can Öztürk'],
            ['id' => 6, 'name' => 'Selin Koç'],
            ['id' => 7, 'name' => 'Burak Tunç'],
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function agencies(): array
    {
        return AgencyDummyData::options();
    }

    public static function referenceDate(): Carbon
    {
        return Carbon::parse('2026-07-07');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(self::raw())
            ->map(fn (array $activity) => self::enrich($activity))
            ->sortByDesc('occurred_at')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function raw(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

$actions = array_keys(self::actionTypes());
        $agencies = AgencyDummyData::all();
        $users = self::users();
        $ips = [
            '85.105.42.118', '78.189.55.201', '192.168.1.45', '176.88.12.67',
            '95.70.33.144', '212.156.78.90', '88.247.19.55', '10.0.0.24',
            '185.22.14.88', '46.154.92.33',
        ];
        $browsers = [
            'Chrome 124 / macOS Sonoma',
            'Chrome 123 / Windows 11',
            'Safari 17 / iOS 17',
            'Firefox 125 / Windows 11',
            'Edge 124 / Windows 10',
            'Chrome 124 / Android 14',
        ];

        $records = [];
        $baseDate = self::referenceDate()->copy()->setTime(17, 30, 0);

        for ($i = 1; $i <= 125; $i++) {
            $agency = $agencies[($i - 1) % count($agencies)];
            $action = $i === 1 ? 'agency_created' : $actions[$i % count($actions)];
            $user = $users[$i % count($users)];
            $occurredAt = $baseDate->copy()->subHours($i * 2)->subMinutes(($i * 11) % 60);
            $hasChange = in_array($action, [
                'agency_updated', 'agency_deactivated', 'agency_activated',
                'contact_updated', 'earning_updated', 'document_updated',
                'contract_updated', 'contract_renewed',
            ], true);

            $records[] = [
                'id' => $i,
                'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
                'agency_id' => $agency['id'],
                'agency_name' => $agency['company_name'],
                'action' => $action,
                'user_id' => $user['id'],
                'user_name' => $user['name'],
                'ip_address' => $ips[$i % count($ips)],
                'user_agent' => $browsers[$i % count($browsers)],
                'description' => self::descriptionFor($action, $agency['company_name'], $i),
                'old_value' => $hasChange ? self::oldValueFor($action) : null,
                'new_value' => $hasChange ? self::newValueFor($action) : null,
            ];
        }

        return $records;
    }

    private static function descriptionFor(string $action, string $agencyName, int $id): string
    {
        return match ($action) {
            'agency_created' => "{$agencyName} acentesi sisteme kaydedildi.",
            'agency_updated' => "{$agencyName} acente bilgileri güncellendi.",
            'agency_deactivated' => "{$agencyName} pasif duruma alındı.",
            'agency_activated' => "{$agencyName} tekrar aktifleştirildi.",
            'contact_added' => "{$agencyName} için yeni yetkili eklendi.",
            'contact_updated' => "{$agencyName} yetkili bilgileri güncellendi.",
            'courier_assigned' => "{$agencyName} acentesine kurye atandı.",
            'courier_removed' => "{$agencyName} acentesinden kurye ayrıldı.",
            'earning_created' => "{$agencyName} için hakediş kaydı oluşturuldu.",
            'earning_updated' => "{$agencyName} hakediş kaydı güncellendi.",
            'document_uploaded' => "{$agencyName} için yeni evrak yüklendi.",
            'document_updated' => "{$agencyName} evrak bilgileri güncellendi.",
            'contract_created' => "{$agencyName} için yeni sözleşme oluşturuldu.",
            'contract_updated' => "{$agencyName} sözleşme bilgileri güncellendi.",
            'contract_renewed' => "{$agencyName} sözleşmesi yenilendi.",
            default => "Acente işlemi kaydedildi (#{$id}).",
        };
    }

    private static function oldValueFor(string $action): string
    {
        return match ($action) {
            'agency_deactivated' => 'Durum: Aktif',
            'agency_activated' => 'Durum: Pasif',
            'earning_updated' => 'Ödeme Durumu: Bekliyor',
            'contract_renewed' => 'Bitiş: 30.06.2026',
            'document_updated' => 'Geçerlilik: 31.12.2025',
            default => '—',
        };
    }

    private static function newValueFor(string $action): string
    {
        return match ($action) {
            'agency_deactivated' => 'Durum: Pasif',
            'agency_activated' => 'Durum: Aktif',
            'earning_updated' => 'Ödeme Durumu: Ödendi',
            'contract_renewed' => 'Bitiş: 30.06.2027',
            'document_updated' => 'Geçerlilik: 31.12.2026',
            default => 'Güncellendi',
        };
    }

    /**
     * @param  array<string, mixed>  $activity
     * @return array<string, mixed>
     */
    public static function enrich(array $activity): array
    {
        $occurredAt = Carbon::parse($activity['occurred_at']);

        return array_merge($activity, [
            'uuid' => 'aact-'.str_pad((string) $activity['id'], 3, '0', STR_PAD_LEFT),
            'action_label' => self::actionTypes()[$activity['action']] ?? $activity['action'],
            'occurred_at_formatted' => $occurredAt->format('d.m.Y H:i'),
            'occurred_at_date' => $occurredAt->format('d.m.Y'),
            'occurred_at_time' => $occurredAt->format('H:i'),
            'subject_type' => 'agency',
            'subject_id' => $activity['agency_id'],
            'causer_id' => $activity['user_id'],
            'properties' => array_filter([
                'old' => $activity['old_value'] ?? null,
                'attributes' => $activity['new_value'] ?? null,
            ]),
        ]);
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $activity) {
            if ($activity['id'] === $id) {
                return $activity;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, int>
     */
    public static function summarize(array $items): array
    {
        $today = self::referenceDate()->copy()->startOfDay();
        $weekStart = $today->copy()->startOfWeek();

        return [
            'count' => count($items),
            'today' => collect($items)->filter(fn ($a) => Carbon::parse($a['occurred_at'])->isSameDay($today))->count(),
            'this_week' => collect($items)->filter(fn ($a) => Carbon::parse($a['occurred_at'])->gte($weekStart))->count(),
            'this_month' => collect($items)->filter(fn ($a) => Carbon::parse($a['occurred_at'])->isSameMonth($today))->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        $today = self::referenceDate()->copy()->startOfDay();
        $weekStart = $today->copy()->startOfWeek();

        return collect(self::all())
            ->filter(function (array $activity) use ($filters, $today, $weekStart) {
                if (! empty($filters['agency_id']) && $filters['agency_id'] !== 'all') {
                    if ((int) $activity['agency_id'] !== (int) $filters['agency_id']) {
                        return false;
                    }
                }

                if (! empty($filters['user_id']) && $filters['user_id'] !== 'all') {
                    if ((int) $activity['user_id'] !== (int) $filters['user_id']) {
                        return false;
                    }
                }

                if (! empty($filters['action']) && $filters['action'] !== 'all') {
                    if ($activity['action'] !== $filters['action']) {
                        return false;
                    }
                }

                if (! empty($filters['date_range']) && $filters['date_range'] !== 'all') {
                    $occurredAt = Carbon::parse($activity['occurred_at']);

                    $matches = match ($filters['date_range']) {
                        'today' => $occurredAt->isSameDay($today),
                        'this_week' => $occurredAt->gte($weekStart),
                        'this_month' => $occurredAt->isSameMonth($today),
                        'last_7_days' => $occurredAt->gte($today->copy()->subDays(7)),
                        'last_30_days' => $occurredAt->gte($today->copy()->subDays(30)),
                        'this_year' => $occurredAt->year === $today->year,
                        default => true,
                    };

                    if (! $matches) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc('occurred_at')
            ->values()
            ->all();
    }
}
