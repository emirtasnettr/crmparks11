<?php

namespace App\Modules\Agency\Data;

use App\Modules\Agency\Support\AgencyFeatures;
use App\Modules\Courier\Data\CourierDummyData;
use Carbon\Carbon;

class AgencyCourierDummyData
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return collect(self::raw())
            ->map(fn (array $record) => self::enrich($record))
            ->sortByDesc('join_date')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function agencies(): array
    {
        return AgencyDummyData::options();
    }

    /**
     * @return array<int, array{id: int, name: string, phone: string}>
     */
    public static function couriers(): array
    {
        return collect(CourierDummyData::raw())
            ->map(fn (array $courier) => [
                'id' => $courier['id'],
                'name' => trim($courier['first_name'].' '.$courier['last_name']),
                'phone' => $courier['phone'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function vehicleTypes(): array
    {
        return CourierDummyData::vehicleTypes();
    }

    /**
     * @return array<int, string>
     */
    public static function businesses(): array
    {
        return collect(self::all())
            ->pluck('active_business_name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function raw(): array
    {
        $agencyNames = collect(self::agencies())->keyBy('id');

        $rows = [
            ['id' => 1, 'courier_id' => 3, 'agency_id' => 2, 'join_date' => '2024-03-01', 'end_date' => '2025-01-31', 'status' => 'inactive', 'notes' => 'Metro Lojistik dönemi.'],
            ['id' => 2, 'courier_id' => 3, 'agency_id' => 1, 'join_date' => '2025-02-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 3, 'courier_id' => 4, 'agency_id' => 3, 'join_date' => '2023-08-15', 'end_date' => '2025-06-30', 'status' => 'inactive', 'notes' => 'Express Dağıtım geçmişi.'],
            ['id' => 4, 'courier_id' => 4, 'agency_id' => 2, 'join_date' => '2025-07-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 5, 'courier_id' => 6, 'agency_id' => 1, 'join_date' => '2025-11-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 6, 'courier_id' => 6, 'agency_id' => 2, 'join_date' => '2024-06-01', 'end_date' => '2025-10-31', 'status' => 'inactive', 'notes' => 'Önceki acente dönemi.'],
            ['id' => 7, 'courier_id' => 8, 'agency_id' => 3, 'join_date' => '2026-01-10', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 8, 'courier_id' => 10, 'agency_id' => 1, 'join_date' => '2024-01-01', 'end_date' => '2025-12-31', 'status' => 'inactive', 'notes' => 'Hızlı Kurye geçmiş ataması.'],
            ['id' => 9, 'courier_id' => 10, 'agency_id' => 2, 'join_date' => '2026-01-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 10, 'courier_id' => 12, 'agency_id' => 1, 'join_date' => '2025-08-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 11, 'courier_id' => 12, 'agency_id' => 3, 'join_date' => '2023-05-01', 'end_date' => '2025-07-31', 'status' => 'inactive', 'notes' => 'İlk acente deneyimi.'],
            ['id' => 12, 'courier_id' => 15, 'agency_id' => 3, 'join_date' => '2025-04-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 13, 'courier_id' => 17, 'agency_id' => 2, 'join_date' => '2025-09-01', 'end_date' => null, 'status' => 'on_leave', 'notes' => 'İzinli — geri dönüş bekleniyor.'],
            ['id' => 14, 'courier_id' => 17, 'agency_id' => 1, 'join_date' => '2024-02-01', 'end_date' => '2025-08-31', 'status' => 'inactive', 'notes' => 'Hızlı Kurye geçmişi.'],
            ['id' => 15, 'courier_id' => 19, 'agency_id' => 1, 'join_date' => '2026-03-15', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 16, 'courier_id' => 19, 'agency_id' => 2, 'join_date' => '2025-01-01', 'end_date' => '2026-03-14', 'status' => 'inactive', 'notes' => 'Acente değişikliği.'],
            ['id' => 17, 'courier_id' => 22, 'agency_id' => 3, 'join_date' => '2026-02-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 18, 'courier_id' => 24, 'agency_id' => 2, 'join_date' => '2025-06-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 19, 'courier_id' => 26, 'agency_id' => 1, 'join_date' => '2025-10-01', 'end_date' => null, 'status' => 'on_leave', 'notes' => 'Sağlık izni.'],
            ['id' => 20, 'courier_id' => 26, 'agency_id' => 2, 'join_date' => '2024-04-01', 'end_date' => '2025-09-30', 'status' => 'inactive', 'notes' => null],
            ['id' => 21, 'courier_id' => 29, 'agency_id' => 3, 'join_date' => '2025-12-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 22, 'courier_id' => 31, 'agency_id' => 2, 'join_date' => '2026-04-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 23, 'courier_id' => 31, 'agency_id' => 1, 'join_date' => '2023-11-01', 'end_date' => '2026-03-31', 'status' => 'inactive', 'notes' => 'Uzun dönem Hızlı Kurye.'],
            ['id' => 24, 'courier_id' => 5, 'agency_id' => 4, 'join_date' => '2024-09-01', 'end_date' => '2025-08-31', 'status' => 'inactive', 'notes' => 'Anadolu Kurye geçmişi.'],
            ['id' => 25, 'courier_id' => 11, 'agency_id' => 4, 'join_date' => '2025-05-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 26, 'courier_id' => 13, 'agency_id' => 5, 'join_date' => '2025-07-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 27, 'courier_id' => 16, 'agency_id' => 5, 'join_date' => '2024-12-01', 'end_date' => '2026-01-31', 'status' => 'inactive', 'notes' => 'Bursa Ekspres ayrılış.'],
            ['id' => 28, 'courier_id' => 18, 'agency_id' => 6, 'join_date' => '2026-05-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 29, 'courier_id' => 20, 'agency_id' => 7, 'join_date' => '2025-03-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 30, 'courier_id' => 21, 'agency_id' => 7, 'join_date' => '2024-07-01', 'end_date' => '2025-11-30', 'status' => 'inactive', 'notes' => null],
            ['id' => 31, 'courier_id' => 23, 'agency_id' => 8, 'join_date' => '2025-08-15', 'end_date' => null, 'status' => 'inactive', 'notes' => 'Pasif kayıt.'],
            ['id' => 32, 'courier_id' => 25, 'agency_id' => 9, 'join_date' => '2026-06-01', 'end_date' => null, 'status' => 'active', 'notes' => 'Yeni katılım.'],
            ['id' => 33, 'courier_id' => 27, 'agency_id' => 10, 'join_date' => '2025-01-15', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 34, 'courier_id' => 28, 'agency_id' => 11, 'join_date' => '2024-10-01', 'end_date' => '2026-02-28', 'status' => 'inactive', 'notes' => 'Kayseri dönemi sona erdi.'],
            ['id' => 35, 'courier_id' => 30, 'agency_id' => 12, 'join_date' => '2026-07-01', 'end_date' => null, 'status' => 'active', 'notes' => 'Bu ay eklenen kurye.'],
            ['id' => 36, 'courier_id' => 32, 'agency_id' => 13, 'join_date' => '2025-11-20', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 37, 'courier_id' => 14, 'agency_id' => 14, 'join_date' => '2024-05-01', 'end_date' => '2025-12-31', 'status' => 'inactive', 'notes' => 'Kaan Aydın — geçmiş kayıt.'],
            ['id' => 38, 'courier_id' => 14, 'agency_id' => 1, 'join_date' => '2026-07-05', 'end_date' => null, 'status' => 'active', 'notes' => 'Bu ay acenteye katıldı.'],
            ['id' => 39, 'courier_id' => 7, 'agency_id' => 15, 'join_date' => '2025-02-01', 'end_date' => '2026-04-30', 'status' => 'inactive', 'notes' => 'Mersin dönemi.'],
            ['id' => 40, 'courier_id' => 9, 'agency_id' => 16, 'join_date' => '2026-07-10', 'end_date' => null, 'status' => 'active', 'notes' => 'Bu hafta atandı.'],
            ['id' => 41, 'courier_id' => 2, 'agency_id' => 17, 'join_date' => '2024-11-01', 'end_date' => '2025-10-31', 'status' => 'inactive', 'notes' => 'Murat Kaya — esnaf iken acente denemesi.'],
            ['id' => 42, 'courier_id' => 1, 'agency_id' => 4, 'join_date' => '2023-01-01', 'end_date' => '2024-06-30', 'status' => 'inactive', 'notes' => 'Ahmet Yıldız geçmiş acente kaydı.'],
            ['id' => 43, 'courier_id' => 28, 'agency_id' => 18, 'join_date' => '2026-03-01', 'end_date' => null, 'status' => 'on_leave', 'notes' => 'İzinli.'],
            ['id' => 44, 'courier_id' => 23, 'agency_id' => 19, 'join_date' => '2023-09-01', 'end_date' => '2025-07-31', 'status' => 'inactive', 'notes' => 'Balıkesir geçmişi.'],
            ['id' => 45, 'courier_id' => 32, 'agency_id' => 2, 'join_date' => '2024-03-01', 'end_date' => '2025-11-19', 'status' => 'inactive', 'notes' => 'Metro Lojistik geçmiş dönem.'],
        ];

        return collect($rows)
            ->map(function (array $row) use ($agencyNames) {
                $row['agency_name'] = $agencyNames[$row['agency_id']]['name'] ?? '—';

                return $row;
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    public static function enrich(array $record): array
    {
        $courier = collect(CourierDummyData::raw())->firstWhere('id', $record['courier_id']);
        $joinDate = Carbon::parse($record['join_date']);
        $endDate = $record['end_date'] ? Carbon::parse($record['end_date']) : null;
        $durationEnd = $endDate ?? Carbon::parse('2026-07-07');
        $months = max(1, $joinDate->diffInMonths($durationEnd));

        $avatarColors = [
            'bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-amber-500',
            'bg-rose-500', 'bg-cyan-500', 'bg-indigo-500', 'bg-orange-500',
        ];

        $plates = [
            'motorcycle' => '34 ABC 123',
            'car' => '34 DEF 456',
            'ebike' => '—',
            'bicycle' => '—',
            'pedestrian' => '—',
        ];

        $vehicleType = $courier['vehicle_type'] ?? 'motorcycle';

        $enriched = [
            'uuid' => 'agcr-'.str_pad((string) $record['id'], 3, '0', STR_PAD_LEFT),
            'courier_name' => $courier ? trim($courier['first_name'].' '.$courier['last_name']) : '—',
            'phone' => $courier['phone'] ?? '—',
            'vehicle_type' => $vehicleType,
            'vehicle_type_label' => CourierDummyData::vehicleTypes()[$vehicleType] ?? '—',
            'active_business_name' => $courier['active_business_name'] ?? null,
            'courier_type' => 'agency',
            'courier_type_label' => 'Acente Kuryesi',
            'avatar_initials' => $courier
                ? mb_strtoupper(mb_substr($courier['first_name'], 0, 1).mb_substr($courier['last_name'], 0, 1))
                : '—',
            'avatar_color' => $avatarColors[($record['courier_id'] - 1) % count($avatarColors)],
            'join_date_formatted' => $joinDate->format('d.m.Y'),
            'end_date_formatted' => $endDate?->format('d.m.Y'),
            'is_current' => $record['end_date'] === null,
            'vehicle_plate' => $plates[$vehicleType] ?? '—',
            'vehicle_info' => (CourierDummyData::vehicleTypes()[$vehicleType] ?? '—').($plates[$vehicleType] !== '—' ? ' · '.$plates[$vehicleType] : ''),
            'work_duration' => $months >= 12
                ? intdiv($months, 12).' yıl '.($months % 12).' ay'
                : $months.' ay',
        ];

        if (AgencyFeatures::earningsEnabled()) {
            $earnings = [3200, 4150, 5280, 6100, 7450, 8900, 9200, 10500];
            $enriched['last_earning_formatted'] = '₺'.number_format($earnings[$record['id'] % count($earnings)], 2, ',', '.');
        }

        return array_merge($record, $enriched);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public static function summarize(array $filters = []): array
    {
        $items = self::filter($filters);
        $thisMonthStart = Carbon::parse('2026-07-01');

        return [
            'total' => count($items),
            'active' => collect($items)->where('status', 'active')->count(),
            'inactive' => collect($items)->where('status', 'inactive')->count(),
            'this_month' => collect($items)->filter(fn ($r) => Carbon::parse($r['join_date'])->gte($thisMonthStart))->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        return collect(self::all())
            ->filter(function (array $record) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $record['courier_name'],
                        $record['phone'],
                    ]));

                    if (! str_contains($haystack, $search)) {
                        return false;
                    }
                }

                if (! empty($filters['agency_id']) && $filters['agency_id'] !== 'all') {
                    if ((int) $record['agency_id'] !== (int) $filters['agency_id']) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($record['status'] !== $filters['status']) {
                        return false;
                    }
                }

                if (! empty($filters['vehicle_type']) && $filters['vehicle_type'] !== 'all') {
                    if ($record['vehicle_type'] !== $filters['vehicle_type']) {
                        return false;
                    }
                }

                if (! empty($filters['active_business']) && $filters['active_business'] !== 'all') {
                    if (($record['active_business_name'] ?? '') !== $filters['active_business']) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc('join_date')
            ->values()
            ->all();
    }
}
