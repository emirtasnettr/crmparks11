<?php

namespace App\Modules\Courier\Data;

use Carbon\Carbon;

class CourierActivityDummyData
{
    /**
     * @return array<string, string>
     */
    public static function actionTypes(): array
    {
        return [
            'courier_created' => 'Kurye Oluşturuldu',
            'courier_updated' => 'Kurye Güncellendi',
            'courier_deactivated' => 'Kurye Pasife Alındı',
            'courier_activated' => 'Kurye Aktifleştirildi',
            'document_uploaded' => 'Belge Yüklendi',
            'document_updated' => 'Belge Güncellendi',
            'vehicle_added' => 'Araç Eklendi',
            'vehicle_updated' => 'Araç Güncellendi',
            'bank_account_added' => 'Banka Hesabı Eklendi',
            'bank_account_updated' => 'Banka Hesabı Güncellendi',
            'earning_created' => 'Hakediş Oluşturuldu',
            'earning_updated' => 'Hakediş Güncellendi',
            'assigned_to_business' => 'İşletmeye Atandı',
            'removed_from_business' => 'İşletmeden Ayrıldı',
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
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function couriers(): array
    {
        return collect(CourierDummyData::all())
            ->map(fn (array $courier) => [
                'id' => $courier['id'],
                'name' => $courier['full_name'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
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
        $actions = array_keys(self::actionTypes());
        $couriers = CourierDummyData::all();
        $users = self::users();
        $ips = [
            '85.105.42.118', '78.189.55.201', '192.168.1.45', '176.88.12.67',
            '95.70.33.144', '212.156.78.90', '88.247.19.55', '10.0.0.24',
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
        $baseDate = Carbon::parse('2026-07-07 17:30:00');

        for ($i = 1; $i <= 110; $i++) {
            $courier = $couriers[($i - 1) % count($couriers)];
            $action = $i === 1 ? 'courier_created' : $actions[$i % count($actions)];
            $user = $users[$i % count($users)];
            $occurredAt = $baseDate->copy()->subHours($i * 2)->subMinutes(($i * 7) % 60);
            $hasChange = in_array($action, [
                'courier_updated', 'courier_deactivated', 'courier_activated',
                'document_updated', 'vehicle_updated', 'bank_account_updated', 'earning_updated',
            ], true);

            $records[] = [
                'id' => $i,
                'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
                'courier_id' => $courier['id'],
                'courier_name' => $courier['full_name'],
                'action' => $action,
                'user_id' => $user['id'],
                'user_name' => $user['name'],
                'ip_address' => $ips[$i % count($ips)],
                'user_agent' => $browsers[$i % count($browsers)],
                'description' => self::descriptionFor($action, $courier['full_name'], $i),
                'old_value' => $hasChange ? self::oldValueFor($action) : null,
                'new_value' => $hasChange ? self::newValueFor($action) : null,
            ];
        }

        return $records;
    }

    private static function descriptionFor(string $action, string $courierName, int $id): string
    {
        return match ($action) {
            'courier_created' => "{$courierName} kuryesi sisteme kaydedildi.",
            'courier_updated' => "{$courierName} profil bilgileri güncellendi.",
            'courier_deactivated' => "{$courierName} pasif duruma alındı.",
            'courier_activated' => "{$courierName} tekrar aktifleştirildi.",
            'document_uploaded' => "{$courierName} için yeni belge yüklendi.",
            'document_updated' => "{$courierName} belge bilgileri güncellendi.",
            'vehicle_added' => "{$courierName} için yeni araç kaydı oluşturuldu.",
            'vehicle_updated' => "{$courierName} araç bilgileri güncellendi.",
            'bank_account_added' => "{$courierName} için banka hesabı eklendi.",
            'bank_account_updated' => "{$courierName} banka hesabı güncellendi.",
            'earning_created' => "{$courierName} için hakediş kaydı oluşturuldu.",
            'earning_updated' => "{$courierName} hakediş kaydı güncellendi.",
            'assigned_to_business' => "{$courierName} işletmeye atandı.",
            'removed_from_business' => "{$courierName} işletme atamasından ayrıldı.",
            default => "Kurye işlemi kaydedildi (#{$id}).",
        };
    }

    private static function oldValueFor(string $action): string
    {
        return match ($action) {
            'courier_deactivated' => 'Durum: Aktif',
            'courier_activated' => 'Durum: Pasif',
            'earning_updated' => 'Ödeme Durumu: Bekliyor',
            'bank_account_updated' => 'Varsayılan: Hayır',
            default => '—',
        };
    }

    private static function newValueFor(string $action): string
    {
        return match ($action) {
            'courier_deactivated' => 'Durum: Pasif',
            'courier_activated' => 'Durum: Aktif',
            'earning_updated' => 'Ödeme Durumu: Ödendi',
            'bank_account_updated' => 'Varsayılan: Evet',
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
            'uuid' => 'cact-'.str_pad((string) $activity['id'], 3, '0', STR_PAD_LEFT),
            'action_label' => self::actionTypes()[$activity['action']] ?? $activity['action'],
            'occurred_at_formatted' => $occurredAt->format('d.m.Y H:i'),
            'occurred_at_date' => $occurredAt->format('d.m.Y'),
            'occurred_at_time' => $occurredAt->format('H:i'),
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
        $today = Carbon::today();
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
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek();

        return collect(self::all())
            ->filter(function (array $activity) use ($filters, $today, $weekStart) {
                if (! empty($filters['courier_id']) && $filters['courier_id'] !== 'all') {
                    if ((int) $activity['courier_id'] !== (int) $filters['courier_id']) {
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
