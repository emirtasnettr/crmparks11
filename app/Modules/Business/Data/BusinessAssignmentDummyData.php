<?php

namespace App\Modules\Business\Data;

use App\Support\DemoData;
use Carbon\Carbon;

class BusinessAssignmentDummyData
{
    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function agencies(): array
    {
        return \App\Modules\Agency\Data\AgencyDummyData::options();
    }

    /**
     * @return array<int, array{id: int, name: string, phone: string, courier_type: string, agency_id: int|null}>
     */
    public static function couriers(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return [
            ['id' => 1, 'name' => 'Ahmet Yıldız', 'phone' => '0532 100 10 01', 'courier_type' => 'independent', 'agency_id' => null],
            ['id' => 2, 'name' => 'Murat Kaya', 'phone' => '0533 100 10 02', 'courier_type' => 'independent', 'agency_id' => null],
            ['id' => 3, 'name' => 'Emre Demir', 'phone' => '0534 100 10 03', 'courier_type' => 'agency', 'agency_id' => 1],
            ['id' => 4, 'name' => 'Serkan Öz', 'phone' => '0535 100 10 04', 'courier_type' => 'agency', 'agency_id' => 2],
            ['id' => 5, 'name' => 'Volkan Arslan', 'phone' => '0536 100 10 05', 'courier_type' => 'independent', 'agency_id' => null],
            ['id' => 6, 'name' => 'Burak Şen', 'phone' => '0537 100 10 06', 'courier_type' => 'agency', 'agency_id' => 1],
            ['id' => 7, 'name' => 'Cem Akın', 'phone' => '0538 100 10 07', 'courier_type' => 'independent', 'agency_id' => null],
            ['id' => 8, 'name' => 'Deniz Polat', 'phone' => '0539 100 10 08', 'courier_type' => 'agency', 'agency_id' => 3],
            ['id' => 9, 'name' => 'Efe Yalçın', 'phone' => '0541 100 10 09', 'courier_type' => 'independent', 'agency_id' => null],
            ['id' => 10, 'name' => 'Furkan Güneş', 'phone' => '0542 100 10 10', 'courier_type' => 'agency', 'agency_id' => 2],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

$assignments = [
            // Ahmet Yıldız — geçmiş: Burger House, sonra Napoli, tekrar Burger House
            ['id' => 1, 'courier_id' => 1, 'business_id' => 1, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-01-01', 'end_date' => '2025-03-31', 'status' => 'inactive', 'notes' => 'İlk dönem ataması.'],
            ['id' => 2, 'courier_id' => 1, 'business_id' => 2, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-04-01', 'end_date' => '2025-12-31', 'status' => 'inactive', 'notes' => 'Napoli Pizza dönemi.'],
            ['id' => 3, 'courier_id' => 1, 'business_id' => 1, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-01-01', 'end_date' => null, 'status' => 'active', 'notes' => 'Burger House\'a geri dönüş.'],

            // Murat Kaya — Yeşil Market geçmiş, şimdi HızlıAl
            ['id' => 4, 'courier_id' => 2, 'business_id' => 3, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-06-01', 'end_date' => '2026-02-28', 'status' => 'inactive', 'notes' => null],
            ['id' => 5, 'courier_id' => 2, 'business_id' => 4, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-03-01', 'end_date' => null, 'status' => 'active', 'notes' => 'E-ticaret operasyonu.'],

            // Emre Demir — acente kuryesi
            ['id' => 6, 'courier_id' => 3, 'business_id' => 1, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2026-02-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 7, 'courier_id' => 3, 'business_id' => 5, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2025-08-01', 'end_date' => '2026-01-31', 'status' => 'inactive', 'notes' => 'Kahve Durağı geçmiş atama.'],

            // Serkan Öz
            ['id' => 8, 'courier_id' => 4, 'business_id' => 2, 'agency_id' => 2, 'courier_type' => 'agency', 'start_date' => '2026-01-15', 'end_date' => '2026-07-20', 'status' => 'active', 'notes' => 'Yakında ayrılacak.'],
            ['id' => 9, 'courier_id' => 4, 'business_id' => 6, 'agency_id' => 2, 'courier_type' => 'agency', 'start_date' => '2025-03-01', 'end_date' => '2025-12-31', 'status' => 'inactive', 'notes' => 'Tatlı Diyarı geçmiş.'],

            // Volkan Arslan — aktif çoklu geçmiş
            ['id' => 10, 'courier_id' => 5, 'business_id' => 7, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-01-01', 'end_date' => '2025-06-30', 'status' => 'inactive', 'notes' => null],
            ['id' => 11, 'courier_id' => 5, 'business_id' => 8, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-07-01', 'end_date' => '2026-04-30', 'status' => 'inactive', 'notes' => null],
            ['id' => 12, 'courier_id' => 5, 'business_id' => 7, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-05-01', 'end_date' => null, 'status' => 'active', 'notes' => 'Usta Kasap aktif.'],

            // Burak Şen
            ['id' => 13, 'courier_id' => 6, 'business_id' => 4, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2026-04-01', 'end_date' => null, 'status' => 'active', 'notes' => null],

            // Cem Akın — geçmiş farklı işletmeler
            ['id' => 14, 'courier_id' => 7, 'business_id' => 1, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-02-01', 'end_date' => '2025-05-31', 'status' => 'inactive', 'notes' => null],
            ['id' => 15, 'courier_id' => 7, 'business_id' => 3, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-06-01', 'end_date' => '2025-11-30', 'status' => 'inactive', 'notes' => null],
            ['id' => 16, 'courier_id' => 7, 'business_id' => 5, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-12-01', 'end_date' => '2026-06-30', 'status' => 'active', 'notes' => 'Kahve Durağı — yakında bitiyor.'],

            // Deniz Polat
            ['id' => 17, 'courier_id' => 8, 'business_id' => 6, 'agency_id' => 3, 'courier_type' => 'agency', 'start_date' => '2026-03-01', 'end_date' => null, 'status' => 'active', 'notes' => null],

            // Efe Yalçın — geçmiş
            ['id' => 18, 'courier_id' => 9, 'business_id' => 2, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2025-09-01', 'end_date' => '2026-03-31', 'status' => 'inactive', 'notes' => 'Napoli geçmiş atama.'],
            ['id' => 19, 'courier_id' => 9, 'business_id' => 8, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-04-01', 'end_date' => null, 'status' => 'active', 'notes' => null],

            // Furkan Güneş
            ['id' => 20, 'courier_id' => 10, 'business_id' => 3, 'agency_id' => 2, 'courier_type' => 'agency', 'start_date' => '2026-01-01', 'end_date' => null, 'status' => 'active', 'notes' => null],
            ['id' => 21, 'courier_id' => 10, 'business_id' => 4, 'agency_id' => 2, 'courier_type' => 'agency', 'start_date' => '2025-04-01', 'end_date' => '2025-12-31', 'status' => 'inactive', 'notes' => 'HızlıAl geçmiş.'],

            // Ek aktif atamalar
            ['id' => 22, 'courier_id' => 2, 'business_id' => 1, 'agency_id' => null, 'courier_type' => 'independent', 'start_date' => '2026-05-01', 'end_date' => null, 'status' => 'active', 'notes' => 'Burger House destek.'],
            ['id' => 23, 'courier_id' => 6, 'business_id' => 2, 'agency_id' => 1, 'courier_type' => 'agency', 'start_date' => '2026-06-01', 'end_date' => '2026-07-15', 'status' => 'active', 'notes' => 'Kısa dönem — yakında ayrılıyor.'],
        ];

        return array_map(fn (array $a) => self::enrich($a), $assignments);
    }

    /**
     * @param  array<string, mixed>  $assignment
     * @return array<string, mixed>
     */
    public static function enrich(array $assignment): array
    {
        $today = Carbon::today();
        $courier = collect(self::couriers())->firstWhere('id', $assignment['courier_id']);
        $business = collect(BusinessDummyData::all())->firstWhere('id', $assignment['business_id']);
        $agency = $assignment['agency_id']
            ? collect(self::agencies())->firstWhere('id', $assignment['agency_id'])
            : null;

        $startDate = Carbon::parse($assignment['start_date']);
        $endDate = $assignment['end_date'] ? Carbon::parse($assignment['end_date']) : null;

        $workStatus = self::resolveWorkStatus($assignment['status'], $endDate, $today);

        return array_merge($assignment, [
            'courier_name' => $courier['name'] ?? '—',
            'courier_phone' => $courier['phone'] ?? '—',
            'business_name' => $business['company_name'] ?? '—',
            'business_brand' => $business['brand_name'] ?? '—',
            'agency_name' => $agency['name'] ?? '—',
            'courier_type_label' => $assignment['courier_type'] === 'agency'
                ? (($agency['name'] ?? null) ?: '—')
                : 'Esnaf Kurye',
            'work_status' => $workStatus,
            'start_date_formatted' => $startDate->format('d.m.Y'),
            'end_date_formatted' => $endDate?->format('d.m.Y') ?? '—',
            'is_active_assignment' => $assignment['status'] === 'active' && $workStatus !== 'left',
        ]);
    }

    private static function resolveWorkStatus(string $status, ?Carbon $endDate, Carbon $today): string
    {
        if ($status === 'inactive' || ($endDate && $endDate->lt($today))) {
            return 'left';
        }

        if ($endDate && $endDate->gte($today) && $today->diffInDays($endDate, false) <= 14) {
            return 'leaving_soon';
        }

        return 'active';
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $assignment) {
            if ($assignment['id'] === $id) {
                return $assignment;
            }
        }

        return null;
    }

    public static function countActive(): int
    {
        return collect(self::all())->where('is_active_assignment', true)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        return collect(self::all())
            ->filter(function (array $assignment) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower($assignment['courier_name'].' '.$assignment['courier_phone']);

                    if (! str_contains($haystack, $search)) {
                        return false;
                    }
                }

                if (! empty($filters['business_id']) && $filters['business_id'] !== 'all') {
                    if ((int) $assignment['business_id'] !== (int) $filters['business_id']) {
                        return false;
                    }
                }

                if (! empty($filters['agency_id']) && $filters['agency_id'] !== 'all') {
                    if ((int) ($assignment['agency_id'] ?? 0) !== (int) $filters['agency_id']) {
                        return false;
                    }
                }

                if (! empty($filters['courier_type']) && $filters['courier_type'] !== 'all') {
                    if ($assignment['courier_type'] !== $filters['courier_type']) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($assignment['status'] !== $filters['status']) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(fn (array $a) => sprintf('%d-%s', $a['is_active_assignment'] ? 1 : 0, $a['start_date']))
            ->values()
            ->all();
    }
}
