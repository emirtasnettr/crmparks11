<?php

namespace App\Modules\Agency\Data;

use App\Support\DemoData;
use Carbon\Carbon;

class AgencyContractDummyData
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(self::raw())
            ->map(fn (array $contract) => self::enrich($contract))
            ->sortByDesc('is_current')
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function contractTypes(): array
    {
        return [
            'service' => 'Hizmet Sözleşmesi',
            'commission' => 'Komisyon Sözleşmesi',
            'framework' => 'Çerçeve Sözleşme',
            'courier_supply' => 'Kurye Tedarik Sözleşmesi',
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function agencies(): array
    {
        return AgencyDummyData::options();
    }

    /**
     * @return array<string, string>
     */
    public static function startDateFilters(): array
    {
        return [
            'this_month' => 'Bu Ay Başlayanlar',
            'this_year' => 'Bu Yıl Başlayanlar',
            'last_30_days' => 'Son 30 Gün',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function endDateFilters(): array
    {
        return [
            'expiring_soon' => '30 Gün İçinde Bitenler',
            'this_month' => 'Bu Ay Bitenler',
            'expired' => 'Süresi Dolmuş',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function raw(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

$agencyNames = collect(self::agencies())->keyBy('id');
        $types = self::contractTypes();

        $rows = [
            ['id' => 1, 'agency_id' => 1, 'contract_number' => 'ACS-2026-001', 'contract_type' => 'service', 'start_date' => '2025-01-01', 'end_date' => '2026-12-31', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => true, 'notes' => 'Ana hizmet sözleşmesi.', 'file_name' => 'hizli-kurye-sozlesme-2026.pdf'],
            ['id' => 2, 'agency_id' => 1, 'contract_number' => 'ACS-2024-012', 'contract_type' => 'service', 'start_date' => '2024-01-01', 'end_date' => '2024-12-31', 'manual_status' => null, 'is_current' => false, 'auto_renewal' => false, 'notes' => 'Arşiv kaydı.', 'file_name' => 'hizli-kurye-sozlesme-2024.pdf'],
            ['id' => 3, 'agency_id' => 2, 'contract_number' => 'ACS-2026-014', 'contract_type' => 'framework', 'start_date' => '2026-01-15', 'end_date' => '2026-07-20', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => false, 'notes' => '13 gün içinde bitiyor.', 'file_name' => 'metro-lojistik-cerceve.pdf'],
            ['id' => 4, 'agency_id' => 2, 'contract_number' => 'ACS-2023-008', 'contract_type' => 'commission', 'start_date' => '2023-06-01', 'end_date' => '2025-12-31', 'manual_status' => null, 'is_current' => false, 'auto_renewal' => false, 'notes' => 'Süresi dolmuş komisyon sözleşmesi.', 'file_name' => 'metro-lojistik-2023.pdf'],
            ['id' => 5, 'agency_id' => 3, 'contract_number' => 'ACS-2026-022', 'contract_type' => 'courier_supply', 'start_date' => '2026-03-01', 'end_date' => '2027-02-28', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => true, 'notes' => null, 'file_name' => 'express-dagitim-tedarik.pdf'],
            ['id' => 6, 'agency_id' => 4, 'contract_number' => 'ACS-2026-031', 'contract_type' => 'service', 'start_date' => '2026-02-01', 'end_date' => '2026-07-17', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => false, 'notes' => '10 gün içinde bitiyor.', 'file_name' => 'anadolu-kurye-hizmet.pdf'],
            ['id' => 7, 'agency_id' => 5, 'contract_number' => 'ACS-2025-045', 'contract_type' => 'service', 'start_date' => '2025-06-01', 'end_date' => '2026-05-31', 'manual_status' => null, 'is_current' => false, 'auto_renewal' => false, 'notes' => 'Süresi dolmuş.', 'file_name' => 'bursa-ekspres-2025.pdf'],
            ['id' => 8, 'agency_id' => 5, 'contract_number' => 'ACS-2026-048', 'contract_type' => 'commission', 'start_date' => '2026-06-01', 'end_date' => '2027-05-31', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => true, 'notes' => 'Yeni dönem aktif.', 'file_name' => 'bursa-ekspres-2026.pdf'],
            ['id' => 9, 'agency_id' => 6, 'contract_number' => null, 'contract_type' => 'framework', 'start_date' => '2026-07-01', 'end_date' => '2026-08-01', 'manual_status' => 'draft', 'is_current' => false, 'auto_renewal' => false, 'notes' => 'Taslak — onay bekliyor.', 'file_name' => null],
            ['id' => 10, 'agency_id' => 7, 'contract_number' => 'ACS-2026-055', 'contract_type' => 'courier_supply', 'start_date' => '2026-01-01', 'end_date' => '2026-07-27', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => true, 'notes' => '20 gün içinde bitiyor.', 'file_name' => 'cukurova-tedarik.pdf'],
            ['id' => 11, 'agency_id' => 8, 'contract_number' => 'ACS-2026-061', 'contract_type' => 'service', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => false, 'notes' => null, 'file_name' => 'gaziantep-hizli.pdf'],
            ['id' => 12, 'agency_id' => 9, 'contract_number' => 'ACS-2024-019', 'contract_type' => 'service', 'start_date' => '2024-03-01', 'end_date' => '2025-02-28', 'manual_status' => null, 'is_current' => false, 'auto_renewal' => false, 'notes' => 'Arşiv.', 'file_name' => 'konya-merkez-2024.pdf'],
            ['id' => 13, 'agency_id' => 10, 'contract_number' => 'ACS-2026-068', 'contract_type' => 'framework', 'start_date' => '2026-05-15', 'end_date' => '2026-07-25', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => false, 'notes' => '18 gün içinde bitiyor.', 'file_name' => 'mersin-cerceve.pdf'],
            ['id' => 14, 'agency_id' => 11, 'contract_number' => 'ACS-2026-072', 'contract_type' => 'commission', 'start_date' => '2026-02-15', 'end_date' => '2027-02-14', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => true, 'notes' => null, 'file_name' => 'kayseri-komisyon.pdf'],
            ['id' => 15, 'agency_id' => 12, 'contract_number' => 'ACS-2025-081', 'contract_type' => 'service', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'manual_status' => null, 'is_current' => false, 'auto_renewal' => false, 'notes' => 'Süresi dolmuş.', 'file_name' => 'eskisehir-2025.pdf'],
            ['id' => 16, 'agency_id' => 13, 'contract_number' => 'ACS-2026-085', 'contract_type' => 'courier_supply', 'start_date' => '2026-03-20', 'end_date' => '2027-03-19', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => true, 'notes' => null, 'file_name' => 'karadeniz-tedarik.pdf'],
            ['id' => 17, 'agency_id' => 14, 'contract_number' => 'ACS-2026-090', 'contract_type' => 'service', 'start_date' => '2026-06-01', 'end_date' => '2026-07-15', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => false, 'notes' => '8 gün içinde bitiyor.', 'file_name' => 'samsun-hizmet.pdf'],
            ['id' => 18, 'agency_id' => 15, 'contract_number' => 'ACS-2024-033', 'contract_type' => 'framework', 'start_date' => '2024-06-01', 'end_date' => '2025-05-31', 'manual_status' => null, 'is_current' => false, 'auto_renewal' => false, 'notes' => 'Arşiv kaydı.', 'file_name' => 'denizli-2024.pdf'],
            ['id' => 19, 'agency_id' => 16, 'contract_number' => 'ACS-2026-098', 'contract_type' => 'service', 'start_date' => '2026-01-10', 'end_date' => '2027-01-09', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => true, 'notes' => 'Turizm sezonu sözleşmesi.', 'file_name' => 'mugla-turizm.pdf'],
            ['id' => 20, 'agency_id' => 17, 'contract_number' => 'ACS-2026-102', 'contract_type' => 'commission', 'start_date' => '2026-04-10', 'end_date' => '2027-04-09', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => false, 'notes' => null, 'file_name' => 'kocaeli-komisyon.pdf'],
            ['id' => 21, 'agency_id' => 18, 'contract_number' => 'ACS-2025-110', 'contract_type' => 'service', 'start_date' => '2025-03-01', 'end_date' => '2026-02-28', 'manual_status' => null, 'is_current' => false, 'auto_renewal' => false, 'notes' => 'Süresi dolmuş.', 'file_name' => 'sakarya-2025.pdf'],
            ['id' => 22, 'agency_id' => 19, 'contract_number' => 'ACS-2026-115', 'contract_type' => 'courier_supply', 'start_date' => '2026-05-01', 'end_date' => '2026-07-22', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => false, 'notes' => '15 gün içinde bitiyor.', 'file_name' => 'balikesir-tedarik.pdf'],
            ['id' => 23, 'agency_id' => 20, 'contract_number' => 'ACS-2026-120', 'contract_type' => 'framework', 'start_date' => '2026-02-01', 'end_date' => '2027-01-31', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => true, 'notes' => null, 'file_name' => 'tekirdag-cerceve.pdf'],
            ['id' => 24, 'agency_id' => 4, 'contract_number' => 'ACS-2023-004', 'contract_type' => 'service', 'start_date' => '2023-01-01', 'end_date' => '2024-12-31', 'manual_status' => null, 'is_current' => false, 'auto_renewal' => false, 'notes' => 'Eski dönem.', 'file_name' => 'anadolu-2023.pdf'],
            ['id' => 25, 'agency_id' => 21, 'contract_number' => 'ACS-2026-125', 'contract_type' => 'service', 'start_date' => '2026-06-15', 'end_date' => '2027-06-14', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => false, 'notes' => null, 'file_name' => 'hatay-hizmet.pdf'],
            ['id' => 26, 'agency_id' => 22, 'contract_number' => 'ACS-2026-130', 'contract_type' => 'commission', 'start_date' => '2026-07-01', 'end_date' => '2026-07-19', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => false, 'notes' => '12 gün içinde bitiyor.', 'file_name' => 'malatya-komisyon.pdf'],
            ['id' => 27, 'agency_id' => 25, 'contract_number' => 'ACS-2026-135', 'contract_type' => 'courier_supply', 'start_date' => '2026-01-20', 'end_date' => '2027-01-19', 'manual_status' => null, 'is_current' => true, 'auto_renewal' => true, 'notes' => 'Bölgenin en büyük tedarik sözleşmesi.', 'file_name' => 'aydin-tedarik.pdf'],
            ['id' => 28, 'agency_id' => 3, 'contract_number' => 'ACS-2024-021', 'contract_type' => 'framework', 'start_date' => '2024-04-01', 'end_date' => '2025-03-31', 'manual_status' => null, 'is_current' => false, 'auto_renewal' => false, 'notes' => 'Arşiv.', 'file_name' => 'express-2024.pdf'],
        ];

        return collect($rows)
            ->map(function (array $row) use ($agencyNames, $types) {
                $row['agency_name'] = $agencyNames[$row['agency_id']]['name'] ?? '—';
                $row['contract_type_label'] = $types[$row['contract_type']] ?? $row['contract_type'];

                return $row;
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $contract
     * @return array<string, mixed>
     */
    public static function enrich(array $contract): array
    {
        $today = Carbon::parse('2026-07-07');
        $endDate = Carbon::parse($contract['end_date']);
        $startDate = Carbon::parse($contract['start_date']);
        $remainingDays = (int) $today->diffInDays($endDate, false);

        $status = $contract['manual_status'] ?? self::resolveStatus($startDate, $endDate, $today);

        return array_merge($contract, [
            'uuid' => 'agct-'.str_pad((string) $contract['id'], 3, '0', STR_PAD_LEFT),
            'status' => $status,
            'remaining_days' => $remainingDays,
            'start_date_formatted' => $startDate->format('d.m.Y'),
            'end_date_formatted' => $endDate->format('d.m.Y'),
            'attachments' => self::attachmentsFor($contract['id']),
            'activity_log' => self::activityLogFor($contract['id']),
        ]);
    }

    private static function resolveStatus(Carbon $start, Carbon $end, Carbon $today): string
    {
        if ($end->lt($today)) {
            return 'expired';
        }

        if ($today->diffInDays($end, false) <= 30) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /**
     * @return array<int, array<string, string>>
     */
    private static function attachmentsFor(int $id): array
    {
        return [
            ['name' => 'Ek Protokol.pdf', 'type' => 'PDF', 'uploaded_at' => '12.06.2026'],
            ['name' => 'Komisyon Tablosu.xlsx', 'type' => 'Excel', 'uploaded_at' => '03.05.2026'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private static function activityLogFor(int $id): array
    {
        return [
            ['action' => 'Sözleşme oluşturuldu', 'user' => 'Ahmet Yılmaz', 'date' => '01.01.2026 10:30'],
            ['action' => 'PDF yüklendi', 'user' => 'Elif Demir', 'date' => '02.01.2026 14:15'],
            ['action' => 'Aktif olarak işaretlendi', 'user' => 'Mehmet Kaya', 'date' => '03.01.2026 09:00'],
        ];
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $contract) {
            if ($contract['id'] === $id) {
                return $contract;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public static function summarize(array $filters = []): array
    {
        $items = self::filter($filters);

        return [
            'total' => count($items),
            'active' => collect($items)->where('status', 'active')->count(),
            'expiring_soon' => collect($items)->where('status', 'expiring_soon')->count(),
            'expired' => collect($items)->where('status', 'expired')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        $today = Carbon::parse('2026-07-07');

        return collect(self::all())
            ->filter(function (array $contract) use ($filters, $today) {
                if (! empty($filters['agency_id']) && $filters['agency_id'] !== 'all') {
                    if ((int) $contract['agency_id'] !== (int) $filters['agency_id']) {
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

                if (! empty($filters['start_date']) && $filters['start_date'] !== 'all') {
                    $start = Carbon::parse($contract['start_date']);

                    $matches = match ($filters['start_date']) {
                        'this_month' => $start->month === $today->month && $start->year === $today->year,
                        'this_year' => $start->year === $today->year,
                        'last_30_days' => $start->gte($today->copy()->subDays(30)),
                        default => true,
                    };

                    if (! $matches) {
                        return false;
                    }
                }

                if (! empty($filters['end_date']) && $filters['end_date'] !== 'all') {
                    $end = Carbon::parse($contract['end_date']);

                    $matches = match ($filters['end_date']) {
                        'expiring_soon' => $end->gte($today) && $end->lte($today->copy()->addDays(30)),
                        'expired' => $end->lt($today),
                        'this_month' => $end->month === $today->month && $end->year === $today->year,
                        default => true,
                    };

                    if (! $matches) {
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
