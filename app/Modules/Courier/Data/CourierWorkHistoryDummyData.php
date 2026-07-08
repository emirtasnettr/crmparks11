<?php

namespace App\Modules\Courier\Data;

use App\Modules\Business\Data\BusinessAssignmentDummyData;
use App\Modules\Business\Data\BusinessContactDummyData;
use App\Modules\Business\Data\BusinessDummyData;
use Carbon\Carbon;

class CourierWorkHistoryDummyData
{
    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'completed' => 'Tamamlandı',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function courierTypes(): array
    {
        return CourierDummyData::courierTypes();
    }

    /**
     * @return array<string, string>
     */
    public static function dateRanges(): array
    {
        return [
            'last_7_days' => 'Son 7 Gün',
            'last_30_days' => 'Son 30 Gün',
            'this_month' => 'Bu Ay',
            'last_3_months' => 'Son 3 Ay',
            'this_year' => 'Bu Yıl',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        $raw = [
            ['id' => 1, 'courier_id' => 1, 'business_id' => 1, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-01-01', 'end_date' => '2025-03-31', 'status' => 'completed', 'notes' => 'İlk dönem ataması.'],
            ['id' => 2, 'courier_id' => 1, 'business_id' => 2, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-04-01', 'end_date' => '2025-12-31', 'status' => 'completed', 'notes' => 'Napoli Pizza dönemi.'],
            ['id' => 3, 'courier_id' => 1, 'business_id' => 1, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-01-01', 'end_date' => null, 'status' => 'active', 'notes' => 'Burger House\'a geri dönüş.'],
            ['id' => 4, 'courier_id' => 2, 'business_id' => 3, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-06-01', 'end_date' => '2026-02-28', 'status' => 'completed', 'notes' => null],
            ['id' => 5, 'courier_id' => 2, 'business_id' => 4, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-03-01', 'end_date' => null, 'status' => 'active', 'notes' => 'E-ticaret operasyonu.'],
            ['id' => 6, 'courier_id' => 3, 'business_id' => 1, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2026-02-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 7, 'courier_id' => 3, 'business_id' => 5, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2025-08-01', 'end_date' => '2026-01-31', 'status' => 'completed', 'notes' => 'Kahve Durağı geçmiş atama.'],
            ['id' => 8, 'courier_id' => 4, 'business_id' => 2, 'agency_id' => 2, 'courier_type' => 'agency', 'start_date' => '2026-01-15', 'end_date' => '2026-07-20', 'status' => 'active', 'notes' => 'Yakında ayrılacak.'],
            ['id' => 9, 'courier_id' => 4, 'business_id' => 6, 'agency_id' => 2, 'courier_type' => 'agency', 'start_date' => '2025-03-01', 'end_date' => '2025-12-31', 'status' => 'completed', 'notes' => 'Tatlı Diyarı geçmiş.'],
            ['id' => 10, 'courier_id' => 5, 'business_id' => 7, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-01-01', 'end_date' => '2025-06-30', 'status' => 'completed', 'notes' => null],
            ['id' => 11, 'courier_id' => 5, 'business_id' => 8, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-07-01', 'end_date' => '2026-04-30', 'status' => 'completed', 'notes' => null],
            ['id' => 12, 'courier_id' => 5, 'business_id' => 7, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-05-01', 'end_date' => null, 'status' => 'active', 'notes' => 'Usta Kasap aktif.'],
            ['id' => 13, 'courier_id' => 6, 'business_id' => 4, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2026-04-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 14, 'courier_id' => 7, 'business_id' => 1, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-02-01', 'end_date' => '2025-05-31', 'status' => 'completed', 'notes' => null],
            ['id' => 15, 'courier_id' => 7, 'business_id' => 3, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-06-01', 'end_date' => '2025-11-30', 'status' => 'completed', 'notes' => null],
            ['id' => 16, 'courier_id' => 7, 'business_id' => 5, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-12-01', 'end_date' => '2026-06-30', 'status' => 'completed', 'notes' => 'Kahve Durağı — dönem tamamlandı.'],
            ['id' => 17, 'courier_id' => 8, 'business_id' => 6, 'agency_id' => 3, 'courier_type' => 'agency', 'start_date' => '2026-03-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 18, 'courier_id' => 9, 'business_id' => 2, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-09-01', 'end_date' => '2026-03-31', 'status' => 'completed', 'notes' => 'Napoli geçmiş atama.'],
            ['id' => 19, 'courier_id' => 9, 'business_id' => 8, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-04-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 20, 'courier_id' => 10, 'business_id' => 3, 'agency_id' => 2, 'courier_type' => 'agency', 'start_date' => '2026-01-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 21, 'courier_id' => 10, 'business_id' => 4, 'agency_id' => 2, 'courier_type' => 'agency', 'start_date' => '2025-04-01', 'end_date' => '2025-12-31', 'status' => 'completed', 'notes' => 'HızlıAl geçmiş.'],
            ['id' => 22, 'courier_id' => 2, 'business_id' => 1, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-05-01', 'end_date' => null, 'status' => 'active', 'notes' => 'Burger House destek.'],
            ['id' => 23, 'courier_id' => 6, 'business_id' => 2, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2026-06-01', 'end_date' => '2026-07-15', 'status' => 'active', 'notes' => 'Kısa dönem — yakında ayrılıyor.'],
            ['id' => 24, 'courier_id' => 11, 'business_id' => 1, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-06-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 25, 'courier_id' => 11, 'business_id' => 3, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-01-01', 'end_date' => '2025-08-31', 'status' => 'completed', 'notes' => 'Yeşil Market dönemi.'],
            ['id' => 26, 'courier_id' => 11, 'business_id' => 5, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2024-06-01', 'end_date' => '2024-12-31', 'status' => 'completed', 'notes' => 'Kahve Durağı kısa dönem.'],
            ['id' => 27, 'courier_id' => 12, 'business_id' => 2, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2026-05-15', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 28, 'courier_id' => 12, 'business_id' => 4, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2025-03-01', 'end_date' => '2026-04-30', 'status' => 'completed', 'notes' => null],
            ['id' => 29, 'courier_id' => 12, 'business_id' => 6, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2024-01-01', 'end_date' => '2025-02-28', 'status' => 'completed', 'notes' => 'Tatlı Diyarı ilk dönem.'],
            ['id' => 30, 'courier_id' => 12, 'business_id' => 2, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2025-08-01', 'end_date' => '2026-05-14', 'status' => 'completed', 'notes' => 'Napoli — ikinci dönem.'],
            ['id' => 31, 'courier_id' => 13, 'business_id' => 3, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-07-01', 'end_date' => null, 'status' => 'active', 'notes' => 'Yaya kurye — yeni atama.'],
            ['id' => 32, 'courier_id' => 13, 'business_id' => 7, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-05-01', 'end_date' => '2026-06-30', 'status' => 'completed', 'notes' => null],
            ['id' => 33, 'courier_id' => 14, 'business_id' => 4, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2024-03-01', 'end_date' => '2025-12-31', 'status' => 'completed', 'notes' => 'HızlıAl uzun dönem.'],
            ['id' => 34, 'courier_id' => 14, 'business_id' => 1, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2023-01-01', 'end_date' => '2024-02-28', 'status' => 'completed', 'notes' => 'Burger House ilk dönem.'],
            ['id' => 35, 'courier_id' => 15, 'business_id' => 4, 'agency_id' => 3, 'courier_type' => 'agency', 'start_date' => '2026-04-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 36, 'courier_id' => 15, 'business_id' => 2, 'agency_id' => 3, 'courier_type' => 'agency', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'status' => 'completed', 'notes' => 'Napoli geçmiş.'],
            ['id' => 37, 'courier_id' => 16, 'business_id' => 5, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-07-01', 'end_date' => '2026-07-20', 'status' => 'active', 'notes' => 'Yakında ayrılacak.'],
            ['id' => 38, 'courier_id' => 16, 'business_id' => 1, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-09-01', 'end_date' => '2026-05-31', 'status' => 'completed', 'notes' => null],
            ['id' => 39, 'courier_id' => 17, 'business_id' => 6, 'agency_id' => 2, 'courier_type' => 'agency', 'start_date' => '2025-01-01', 'end_date' => '2026-06-01', 'status' => 'completed', 'notes' => 'İzinli dönem öncesi.'],
            ['id' => 40, 'courier_id' => 18, 'business_id' => 7, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-02-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 41, 'courier_id' => 18, 'business_id' => 3, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2024-08-01', 'end_date' => '2026-01-31', 'status' => 'completed', 'notes' => 'Yeşil Market geçmiş.'],
            ['id' => 42, 'courier_id' => 19, 'business_id' => 1, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2026-03-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 43, 'courier_id' => 19, 'business_id' => 8, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2025-04-01', 'end_date' => '2026-02-28', 'status' => 'completed', 'notes' => null],
            ['id' => 44, 'courier_id' => 20, 'business_id' => 3, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-06-15', 'end_date' => null, 'status' => 'active', 'notes' => 'Yaya kurye.'],
            ['id' => 45, 'courier_id' => 21, 'business_id' => 2, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-05-01', 'end_date' => null, 'status' => 'active', 'notes' => 'Bisiklet kurye.'],
            ['id' => 46, 'courier_id' => 21, 'business_id' => 5, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-01-01', 'end_date' => '2026-04-30', 'status' => 'completed', 'notes' => 'Kahve Durağı geçmiş.'],
            ['id' => 47, 'courier_id' => 22, 'business_id' => 8, 'agency_id' => 3, 'courier_type' => 'agency', 'start_date' => '2026-04-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 48, 'courier_id' => 23, 'business_id' => 4, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2024-01-01', 'end_date' => '2025-06-30', 'status' => 'completed', 'notes' => 'Pasif kurye geçmişi.'],
            ['id' => 49, 'courier_id' => 24, 'business_id' => 4, 'agency_id' => 2, 'courier_type' => 'agency', 'start_date' => '2026-06-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 50, 'courier_id' => 24, 'business_id' => 1, 'agency_id' => 2, 'courier_type' => 'agency', 'start_date' => '2025-08-01', 'end_date' => '2026-05-31', 'status' => 'completed', 'notes' => 'Burger House geçmiş.'],
            ['id' => 51, 'courier_id' => 25, 'business_id' => 5, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-07-05', 'end_date' => null, 'status' => 'active', 'notes' => 'Bu ay başlayan görev.'],
            ['id' => 52, 'courier_id' => 27, 'business_id' => 6, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-02-01', 'end_date' => '2026-06-30', 'status' => 'completed', 'notes' => 'Tatlı Diyarı dönemi.'],
        ];

        return collect($raw)
            ->map(fn (array $row) => self::enrich($row))
            ->sortByDesc(fn ($r) => sprintf('%d-%s', in_array($r['work_status'], ['active', 'leaving_soon'], true) ? 1 : 0, $r['start_date']))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function enrich(array $row): array
    {
        $today = Carbon::today();
        $courier = collect(CourierDummyData::all())->firstWhere('id', $row['courier_id']);
        $business = collect(BusinessDummyData::all())->firstWhere('id', $row['business_id']);
        $agency = $row['agency_id']
            ? collect(BusinessAssignmentDummyData::agencies())->firstWhere('id', $row['agency_id'])
            : null;

        $startDate = Carbon::parse($row['start_date']);
        $endDate = $row['end_date'] ? Carbon::parse($row['end_date']) : null;
        $workStatus = self::resolveWorkStatus($row['status'], $endDate, $today);
        $durationEnd = ($workStatus === 'completed' && $endDate) ? $endDate : $today;

        return array_merge($row, [
            'uuid' => 'cwh-'.str_pad((string) $row['id'], 3, '0', STR_PAD_LEFT),
            'courier_name' => $courier['full_name'] ?? '—',
            'courier_phone' => $courier['phone'] ?? '—',
            'business_name' => $business['company_name'] ?? '—',
            'business_brand' => $business['brand_name'] ?? '—',
            'agency_name' => $agency['name'] ?? '—',
            'courier_type_label' => CourierDummyData::courierTypes()[$row['courier_type']] ?? '—',
            'work_status' => $workStatus,
            'work_status_label' => self::workStatusLabels()[$workStatus] ?? '—',
            'start_date_formatted' => $startDate->format('d.m.Y'),
            'end_date_formatted' => $endDate?->format('d.m.Y') ?? '—',
            'work_duration' => self::formatDuration($startDate, $durationEnd),
            'work_duration_days' => $startDate->diffInDays($durationEnd) + 1,
            'is_ongoing' => in_array($workStatus, ['active', 'leaving_soon'], true),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function workStatusLabels(): array
    {
        return [
            'active' => 'Aktif',
            'completed' => 'Tamamlandı',
            'leaving_soon' => 'Yakında Ayrılıyor',
        ];
    }

    private static function resolveWorkStatus(string $status, ?Carbon $endDate, Carbon $today): string
    {
        if ($status === 'completed' || ($endDate && $endDate->lt($today))) {
            return 'completed';
        }

        if ($endDate && $endDate->gte($today) && $today->diffInDays($endDate, false) <= 14) {
            return 'leaving_soon';
        }

        return 'active';
    }

    private static function formatDuration(Carbon $start, Carbon $end): string
    {
        $days = $start->diffInDays($end) + 1;

        if ($days < 30) {
            return $days.' gün';
        }

        $months = (int) $start->diffInMonths($end);
        $monthAnchor = $start->copy()->addMonths($months);
        $remainingDays = $monthAnchor->lte($end) ? $monthAnchor->diffInDays($end) : 0;

        if ($months < 12) {
            return $remainingDays > 0 ? "{$months} ay {$remainingDays} gün" : "{$months} ay";
        }

        $years = (int) $start->diffInYears($end);
        $yearAnchor = $start->copy()->addYears($years);
        $remainingMonths = $yearAnchor->lte($end) ? (int) $yearAnchor->diffInMonths($end) : 0;

        return $remainingMonths > 0 ? "{$years} yıl {$remainingMonths} ay" : "{$years} yıl";
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $record) {
            if ($record['id'] === $id) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function businesses(): array
    {
        return BusinessContactDummyData::businesses();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function agencies(): array
    {
        return BusinessAssignmentDummyData::agencies();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, int>
     */
    public static function summarize(array $items): array
    {
        $today = Carbon::today();

        return [
            'count' => count($items),
            'active_count' => collect($items)->whereIn('work_status', ['active', 'leaving_soon'])->count(),
            'completed_count' => collect($items)->where('work_status', 'completed')->count(),
            'started_this_month' => collect($items)
                ->filter(function (array $record) use ($today) {
                    $start = Carbon::parse($record['start_date']);

                    return $start->month === $today->month && $start->year === $today->year;
                })
                ->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        $today = Carbon::today();

        return collect(self::all())
            ->filter(function (array $record) use ($filters, $today) {
                if (! empty($filters['courier_id']) && $filters['courier_id'] !== 'all') {
                    if ((int) $record['courier_id'] !== (int) $filters['courier_id']) {
                        return false;
                    }
                }

                if (! empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $record['courier_name'],
                        $record['courier_phone'],
                    ]));

                    if (! str_contains($haystack, $search)) {
                        return false;
                    }
                }

                if (! empty($filters['business_id']) && $filters['business_id'] !== 'all') {
                    if ((int) $record['business_id'] !== (int) $filters['business_id']) {
                        return false;
                    }
                }

                if (! empty($filters['agency_id']) && $filters['agency_id'] !== 'all') {
                    if ((int) ($record['agency_id'] ?? 0) !== (int) $filters['agency_id']) {
                        return false;
                    }
                }

                if (! empty($filters['courier_type']) && $filters['courier_type'] !== 'all') {
                    if ($record['courier_type'] !== $filters['courier_type']) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($filters['status'] === 'active') {
                        if (! in_array($record['work_status'], ['active', 'leaving_soon'], true)) {
                            return false;
                        }
                    } elseif ($record['work_status'] !== 'completed') {
                        return false;
                    }
                }

                if (! empty($filters['date_range']) && $filters['date_range'] !== 'all') {
                    $startDate = Carbon::parse($record['start_date']);
                    $matches = match ($filters['date_range']) {
                        'last_7_days' => $startDate->gte($today->copy()->subDays(7)),
                        'last_30_days' => $startDate->gte($today->copy()->subDays(30)),
                        'this_month' => $startDate->month === $today->month && $startDate->year === $today->year,
                        'last_3_months' => $startDate->gte($today->copy()->subMonths(3)),
                        'this_year' => $startDate->year === $today->year,
                        default => true,
                    };

                    if (! $matches) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(fn ($r) => sprintf('%d-%s', in_array($r['work_status'], ['active', 'leaving_soon'], true) ? 1 : 0, $r['start_date']))
            ->values()
            ->all();
    }
}
