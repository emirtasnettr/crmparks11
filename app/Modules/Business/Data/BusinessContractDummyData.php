<?php

namespace App\Modules\Business\Data;

use Carbon\Carbon;

class BusinessContractDummyData
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        $contracts = [
            [
                'id' => 1,
                'uuid' => 'cnt-001',
                'business_id' => 1,
                'business_name' => 'Burger House Gıda Ltd. Şti.',
                'business_brand' => 'Burger House',
                'contract_number' => 'SZL-2026-001',
                'contract_type' => 'service',
                'contract_type_label' => 'Hizmet Sözleşmesi',
                'start_date' => '2025-01-01',
                'end_date' => '2026-12-31',
                'manual_status' => null,
                'is_current' => true,
                'notes' => 'Yıllık kurye hizmet sözleşmesi.',
                'file_name' => 'burger-house-sozlesme-2026.pdf',
            ],
            [
                'id' => 2,
                'uuid' => 'cnt-002',
                'business_id' => 1,
                'business_name' => 'Burger House Gıda Ltd. Şti.',
                'business_brand' => 'Burger House',
                'contract_number' => 'SZL-2024-018',
                'contract_type' => 'service',
                'contract_type_label' => 'Hizmet Sözleşmesi',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'manual_status' => null,
                'is_current' => false,
                'notes' => 'Eski dönem sözleşmesi.',
                'file_name' => 'burger-house-sozlesme-2024.pdf',
            ],
            [
                'id' => 3,
                'uuid' => 'cnt-003',
                'business_id' => 2,
                'business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.',
                'business_brand' => 'Napoli Pizza',
                'contract_number' => 'SZL-2026-014',
                'contract_type' => 'framework',
                'contract_type_label' => 'Çerçeve Sözleşme',
                'start_date' => '2026-01-15',
                'end_date' => '2026-07-20',
                'manual_status' => null,
                'is_current' => true,
                'notes' => 'Çerçeve sözleşme — yenileme görüşülüyor.',
                'file_name' => 'napoli-pizza-cerceve.pdf',
            ],
            [
                'id' => 4,
                'uuid' => 'cnt-004',
                'business_id' => 3,
                'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.',
                'business_brand' => 'Yeşil Market',
                'contract_number' => 'SZL-2026-022',
                'contract_type' => 'service',
                'contract_type_label' => 'Hizmet Sözleşmesi',
                'start_date' => '2026-03-01',
                'end_date' => '2027-02-28',
                'manual_status' => null,
                'is_current' => true,
                'notes' => null,
                'file_name' => 'yesil-market-hizmet.pdf',
            ],
            [
                'id' => 5,
                'uuid' => 'cnt-005',
                'business_id' => 4,
                'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.',
                'business_brand' => 'HızlıAl',
                'contract_number' => 'SZL-2026-031',
                'contract_type' => 'courier',
                'contract_type_label' => 'Kurye Sözleşmesi',
                'start_date' => '2026-02-01',
                'end_date' => '2026-07-25',
                'manual_status' => null,
                'is_current' => true,
                'notes' => 'E-ticaret dağıtım operasyonu.',
                'file_name' => 'hizlial-kurye.pdf',
            ],
            [
                'id' => 6,
                'uuid' => 'cnt-006',
                'business_id' => 5,
                'business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.',
                'business_brand' => 'Kahve Durağı',
                'contract_number' => 'SZL-2025-089',
                'contract_type' => 'service',
                'contract_type_label' => 'Hizmet Sözleşmesi',
                'start_date' => '2025-06-01',
                'end_date' => '2026-05-31',
                'manual_status' => null,
                'is_current' => false,
                'notes' => 'Süresi dolmuş eski sözleşme.',
                'file_name' => 'kahve-duragi-2025.pdf',
            ],
            [
                'id' => 7,
                'uuid' => 'cnt-007',
                'business_id' => 5,
                'business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.',
                'business_brand' => 'Kahve Durağı',
                'contract_number' => 'SZL-2026-045',
                'contract_type' => 'service',
                'contract_type_label' => 'Hizmet Sözleşmesi',
                'start_date' => '2026-06-01',
                'end_date' => '2027-05-31',
                'manual_status' => null,
                'is_current' => true,
                'notes' => 'Yeni dönem aktif sözleşme.',
                'file_name' => 'kahve-duragi-2026.pdf',
            ],
            [
                'id' => 8,
                'uuid' => 'cnt-008',
                'business_id' => 6,
                'business_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri',
                'business_brand' => 'Tatlı Diyarı',
                'contract_number' => 'SZL-2026-052',
                'contract_type' => 'service',
                'contract_type_label' => 'Hizmet Sözleşmesi',
                'start_date' => '2026-04-01',
                'end_date' => '2026-06-30',
                'manual_status' => null,
                'is_current' => true,
                'notes' => null,
                'file_name' => 'tatli-diyari-hizmet.pdf',
            ],
            [
                'id' => 9,
                'uuid' => 'cnt-009',
                'business_id' => 7,
                'business_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.',
                'business_brand' => 'Usta Kasap',
                'contract_number' => null,
                'contract_type' => 'service',
                'contract_type_label' => 'Hizmet Sözleşmesi',
                'start_date' => '2026-07-01',
                'end_date' => '2026-08-01',
                'manual_status' => 'draft',
                'is_current' => false,
                'notes' => 'Taslak — henüz onaylanmadı.',
                'file_name' => null,
            ],
            [
                'id' => 10,
                'uuid' => 'cnt-010',
                'business_id' => 8,
                'business_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.',
                'business_brand' => 'Taze Manav',
                'contract_number' => 'SZL-2026-061',
                'contract_type' => 'framework',
                'contract_type_label' => 'Çerçeve Sözleşme',
                'start_date' => '2026-01-01',
                'end_date' => '2026-07-17',
                'manual_status' => null,
                'is_current' => true,
                'notes' => '10 gün içinde bitiyor.',
                'file_name' => 'taze-manav-cerceve.pdf',
            ],
            [
                'id' => 11,
                'uuid' => 'cnt-011',
                'business_id' => 2,
                'business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.',
                'business_brand' => 'Napoli Pizza',
                'contract_number' => 'SZL-2024-033',
                'contract_type' => 'service',
                'contract_type_label' => 'Hizmet Sözleşmesi',
                'start_date' => '2024-03-01',
                'end_date' => '2025-02-28',
                'manual_status' => null,
                'is_current' => false,
                'notes' => 'Arşiv kaydı.',
                'file_name' => 'napoli-pizza-2024.pdf',
            ],
            [
                'id' => 12,
                'uuid' => 'cnt-012',
                'business_id' => 4,
                'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.',
                'business_brand' => 'HızlıAl',
                'contract_number' => 'SZL-2026-068',
                'contract_type' => 'courier',
                'contract_type_label' => 'Kurye Sözleşmesi',
                'start_date' => '2026-05-15',
                'end_date' => '2026-07-27',
                'manual_status' => null,
                'is_current' => false,
                'notes' => 'Ek operasyon sözleşmesi.',
                'file_name' => 'hizlial-ek-kurye.pdf',
            ],
        ];

        return array_map(fn (array $contract) => self::enrich($contract), $contracts);
    }

    /**
     * @param  array<string, mixed>  $contract
     * @return array<string, mixed>
     */
    public static function enrich(array $contract): array
    {
        $today = Carbon::today();
        $endDate = Carbon::parse($contract['end_date']);
        $startDate = Carbon::parse($contract['start_date']);
        $remainingDays = $today->diffInDays($endDate, false);

        $status = $contract['manual_status'] ?? self::resolveStatus($startDate, $endDate, $today);

        return array_merge($contract, [
            'status' => $status,
            'remaining_days' => (int) $remainingDays,
            'start_date_formatted' => $startDate->format('d.m.Y'),
            'end_date_formatted' => $endDate->format('d.m.Y'),
        ]);
    }

    private static function resolveStatus(Carbon $start, Carbon $end, Carbon $today): string
    {
        if ($end->lt($today)) {
            return 'expired';
        }

        $daysRemaining = $today->diffInDays($end, false);

        if ($daysRemaining <= 30) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public static function find(int|string $id): ?array
    {
        $id = (int) $id;

        foreach (self::all() as $contract) {
            if ($contract['id'] === $id) {
                return $contract;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public static function contractTypes(): array
    {
        return [
            'service' => 'Hizmet Sözleşmesi',
            'courier' => 'Kurye Sözleşmesi',
            'agency' => 'Acente Sözleşmesi',
            'framework' => 'Çerçeve Sözleşme',
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function businesses(): array
    {
        return BusinessContactDummyData::businesses();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        $today = Carbon::today();

        return collect(self::all())
            ->filter(function (array $contract) use ($filters, $today) {
                if (! empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', array_filter([
                        $contract['contract_number'] ?? '',
                        $contract['business_name'],
                        $contract['contract_type_label'],
                    ])));

                    if (! str_contains($haystack, $search)) {
                        return false;
                    }
                }

                if (! empty($filters['business_id']) && $filters['business_id'] !== 'all') {
                    if ((int) $contract['business_id'] !== (int) $filters['business_id']) {
                        return false;
                    }
                }

                if (! empty($filters['contract_type']) && $filters['contract_type'] !== 'all') {
                    if ($contract['contract_type'] !== $filters['contract_type']) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($contract['status'] !== $filters['status']) {
                        return false;
                    }
                }

                if (! empty($filters['end_date']) && $filters['end_date'] !== 'all') {
                    $end = Carbon::parse($contract['end_date']);

                    $matchesEndDate = match ($filters['end_date']) {
                        'expiring_soon' => $end->gte($today) && $end->lte($today->copy()->addDays(30)),
                        'expired' => $end->lt($today),
                        'this_month' => $end->month === $today->month && $end->year === $today->year,
                        default => true,
                    };

                    if (! $matchesEndDate) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(fn (array $c) => $c['is_current'])
            ->values()
            ->all();
    }
}
