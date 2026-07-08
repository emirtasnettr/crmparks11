<?php

namespace App\Modules\Agency\Data;

use Carbon\Carbon;

class AgencyDocumentDummyData
{
    public const EXPIRY_WARNING_DAYS = 30;

    /**
     * @return array<string, string>
     */
    public static function documentTypes(): array
    {
        return [
            'tax_plate' => 'Vergi Levhası',
            'activity_certificate' => 'Faaliyet Belgesi',
            'trade_registry' => 'Ticaret Sicil Gazetesi',
            'signature_circular' => 'İmza Sirküsü',
            'chamber_registration' => 'Oda Kayıt Belgesi',
            'authorization_certificate' => 'Yetki Belgesi',
            'sgk_clearance' => 'SGK Borcu Yoktur',
            'tax_clearance' => 'Vergi Borcu Yoktur',
            'contract' => 'Sözleşme',
            'other' => 'Diğer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'valid' => 'Geçerli',
            'expiring_soon' => 'Süresi Yaklaşıyor',
            'expired' => 'Süresi Dolmuş',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function expiryFilters(): array
    {
        return [
            'expiring_soon' => '30 Gün İçinde Dolanlar',
            'expired' => 'Süresi Dolmuş',
            'this_month' => 'Bu Ay Dolanlar',
            'next_3_months' => 'Önümüzdeki 3 Ay',
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
     * @return array<int, array<string, mixed>>
     */
    public static function all(bool $withTrashed = false, bool $includeArchivedVersions = false): array
    {
        return collect(self::raw())
            ->filter(function (array $row) use ($withTrashed, $includeArchivedVersions) {
                if (! $withTrashed && $row['deleted_at'] !== null) {
                    return false;
                }

                if (! $includeArchivedVersions && ! $row['is_current']) {
                    return false;
                }

                return true;
            })
            ->map(fn (array $row) => self::enrich($row))
            ->sortByDesc('uploaded_at')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function raw(): array
    {
        $rows = [
            [1, 'grp-001', 1, 'tax_plate', 'VL-1234567890', '2025-11-10', '2027-11-10', 'hizli-kurye-vergi-levhasi.pdf', 2, true, null],
            [2, 'grp-002', 1, 'activity_certificate', 'FB-2024-101', '2024-03-01', '2027-03-01', 'hizli-kurye-faaliyet.pdf', 1, true, null],
            [3, 'grp-003', 1, 'signature_circular', 'IS-2025-044', '2025-06-15', '2026-07-20', 'hizli-kurye-imza-sirkuesi.pdf', 1, true, null],
            [4, 'grp-004', 2, 'tax_plate', 'VL-2345678901', '2025-02-20', '2027-02-20', 'metro-lojistik-vergi.pdf', 1, true, null],
            [5, 'grp-005', 2, 'trade_registry', 'TSG-2023-882', '2023-09-12', '2026-07-15', 'metro-lojistik-ticaret-sicil.pdf', 2, true, null],
            [6, 'grp-006', 2, 'sgk_clearance', 'SGK-2026-118', '2026-06-01', '2026-07-25', 'metro-lojistik-sgk.pdf', 1, true, null],
            [7, 'grp-007', 3, 'activity_certificate', 'FB-2025-203', '2025-04-10', '2028-04-10', 'express-dagitim-faaliyet.pdf', 1, true, null],
            [8, 'grp-008', 3, 'tax_clearance', 'VB-2026-055', '2026-05-20', '2026-07-10', 'express-dagitim-vergi-borcu.pdf', 1, true, null],
            [9, 'grp-009', 3, 'authorization_certificate', 'YB-2024-331', '2024-11-01', '2026-06-20', 'express-dagitim-yetki.pdf', 1, true, null],
            [10, 'grp-010', 4, 'tax_plate', 'VL-4567890123', '2025-01-05', '2027-01-05', 'anadolu-kurye-vergi.pdf', 1, true, null],
            [11, 'grp-011', 4, 'chamber_registration', 'OKB-2025-077', '2025-08-18', '2026-08-01', 'anadolu-kurye-oda-kayit.pdf', 1, true, null],
            [12, 'grp-012', 4, 'contract', 'SZL-2026-004', '2026-01-20', '2027-01-20', 'anadolu-kurye-sozlesme.pdf', 1, true, null],
            [13, 'grp-013', 5, 'trade_registry', 'TSG-2022-441', '2022-07-22', '2026-05-30', 'bursa-ekspres-ticaret-sicil.pdf', 1, true, null],
            [14, 'grp-014', 5, 'sgk_clearance', 'SGK-2026-092', '2026-04-15', '2026-07-28', 'bursa-ekspres-sgk.pdf', 1, true, null],
            [15, 'grp-015', 5, 'signature_circular', 'IS-2025-119', '2025-10-01', '2026-12-31', 'bursa-ekspres-imza.pdf', 1, true, null],
            [16, 'grp-016', 6, 'tax_plate', 'VL-6789012345', '2025-03-12', '2027-03-12', 'akdeniz-dagitim-vergi.pdf', 1, true, null],
            [17, 'grp-017', 6, 'activity_certificate', 'FB-2026-014', '2026-02-01', '2026-07-05', 'akdeniz-dagitim-faaliyet.pdf', 1, true, null],
            [18, 'grp-018', 7, 'tax_clearance', 'VB-2026-078', '2026-03-10', '2026-07-18', 'cukurova-vergi-borcu.pdf', 1, true, null],
            [19, 'grp-019', 7, 'authorization_certificate', 'YB-2025-212', '2025-12-05', '2027-12-05', 'cukurova-yetki.pdf', 1, true, null],
            [20, 'grp-020', 7, 'contract', 'SZL-2025-007', '2025-07-01', '2026-07-01', 'cukurova-sozlesme.pdf', 1, true, null],
            [21, 'grp-021', 8, 'tax_plate', 'VL-8901234567', '2024-09-20', '2026-09-20', 'gaziantep-hizli-vergi.pdf', 1, true, null],
            [22, 'grp-022', 8, 'chamber_registration', 'OKB-2024-156', '2024-06-15', '2026-06-01', 'gaziantep-hizli-oda.pdf', 1, true, null],
            [23, 'grp-023', 10, 'activity_certificate', 'FB-2025-310', '2025-05-08', '2028-05-08', 'mersin-sahil-faaliyet.pdf', 1, true, null],
            [24, 'grp-024', 10, 'sgk_clearance', 'SGK-2026-134', '2026-06-10', '2026-07-22', 'mersin-sahil-sgk.pdf', 1, true, null],
            [25, 'grp-025', 11, 'tax_plate', 'VL-2234567890', '2025-08-01', '2027-08-01', 'kayseri-dagitim-vergi.pdf', 1, true, null],
            [26, 'grp-026', 11, 'trade_registry', 'TSG-2024-559', '2024-12-12', '2026-07-30', 'kayseri-dagitim-ticaret-sicil.pdf', 1, true, null],
            [27, 'grp-027', 11, 'signature_circular', 'IS-2026-028', '2026-01-25', '2027-01-25', 'kayseri-dagitim-imza.pdf', 1, true, null],
            [28, 'grp-028', 13, 'tax_clearance', 'VB-2026-091', '2026-04-01', '2026-07-12', 'karadeniz-vergi-borcu.pdf', 1, true, null],
            [29, 'grp-029', 13, 'contract', 'SZL-2026-013', '2026-02-14', '2027-02-14', 'karadeniz-sozlesme.pdf', 1, true, null],
            [30, 'grp-030', 14, 'activity_certificate', 'FB-2025-418', '2025-11-20', '2026-07-08', 'samsun-hizli-faaliyet.pdf', 1, true, null],
            [31, 'grp-031', 14, 'authorization_certificate', 'YB-2025-287', '2025-09-05', '2027-09-05', 'samsun-hizli-yetki.pdf', 1, true, null],
            [32, 'grp-032', 15, 'tax_plate', 'VL-6678901234', '2025-06-18', '2027-06-18', 'denizli-paket-vergi.pdf', 1, true, null],
            [33, 'grp-033', 16, 'chamber_registration', 'OKB-2025-199', '2025-03-22', '2026-08-15', 'mugla-turizm-oda.pdf', 1, true, null],
            [34, 'grp-034', 16, 'sgk_clearance', 'SGK-2026-156', '2026-05-05', '2026-07-06', 'mugla-turizm-sgk.pdf', 1, true, null],
            [35, 'grp-035', 17, 'tax_plate', 'VL-8890123456', '2024-11-11', '2026-11-11', 'kocaeli-sanayi-vergi.pdf', 1, true, null],
            [36, 'grp-036', 17, 'trade_registry', 'TSG-2023-712', '2023-04-30', '2026-04-10', 'kocaeli-sanayi-ticaret-sicil.pdf', 1, true, null],
            [37, 'grp-037', 20, 'activity_certificate', 'FB-2026-022', '2026-03-15', '2027-03-15', 'tekirdag-marmara-faaliyet.pdf', 1, true, null],
            [38, 'grp-038', 20, 'tax_clearance', 'VB-2026-103', '2026-05-18', '2026-07-31', 'tekirdag-marmara-vergi-borcu.pdf', 1, true, null],
            [39, 'grp-039', 21, 'signature_circular', 'IS-2025-344', '2025-12-20', '2026-12-20', 'hatay-guney-imza.pdf', 1, true, null],
            [40, 'grp-040', 22, 'contract', 'SZL-2025-022', '2025-10-10', '2026-10-10', 'malatya-dogu-sozlesme.pdf', 1, true, null],
            [41, 'grp-041', 24, 'tax_plate', 'VL-6567890123', '2025-07-07', '2027-07-07', 'edirne-sinir-vergi.pdf', 1, true, null],
            [42, 'grp-042', 25, 'authorization_certificate', 'YB-2026-041', '2026-04-08', '2028-04-08', 'aydin-ege-yetki.pdf', 1, true, null],
            [43, 'grp-043', 25, 'sgk_clearance', 'SGK-2026-178', '2026-06-20', '2026-07-14', 'aydin-ege-sgk.pdf', 1, true, null],
            [44, 'grp-044', 18, 'other', 'BLG-2026-009', '2026-01-30', '2026-12-30', 'sakarya-ekspres-diger.pdf', 1, true, null],
            [45, 'grp-045', 23, 'tax_clearance', 'VB-2026-112', '2026-02-28', '2026-06-15', 'van-dogu-vergi-borcu.pdf', 1, true, null],
            [46, 'grp-046', 12, 'activity_certificate', 'FB-2025-512', '2025-09-14', '2026-07-02', 'eskisehir-ekspres-faaliyet.pdf', 1, true, null],
            // Arşivlenmiş eski versiyonlar
            [47, 'grp-001', 1, 'tax_plate', 'VL-1234567890', '2023-01-10', '2025-01-10', 'hizli-kurye-vergi-levhasi-v1.pdf', 1, false, null],
            [48, 'grp-005', 2, 'trade_registry', 'TSG-2021-882', '2021-09-12', '2024-09-12', 'metro-lojistik-ticaret-sicil-v1.pdf', 1, false, null],
            [49, 'grp-005', 2, 'trade_registry', 'TSG-2023-882', '2023-09-12', '2025-09-12', 'metro-lojistik-ticaret-sicil-v2.pdf', 2, false, null],
            [50, 'grp-020', 7, 'contract', 'SZL-2024-007', '2024-07-01', '2025-07-01', 'cukurova-sozlesme-v1.pdf', 1, false, null],
            // Soft delete
            [51, 'grp-047', 2, 'other', 'BLG-2025-099', '2025-12-01', '2026-12-01', 'metro-lojistik-iptal.pdf', 1, true, '2026-06-15'],
        ];

        return collect($rows)->map(function (array $row) {
            [$id, $groupId, $agencyId, $type, $number, $uploadedAt, $expiryDate, $fileName, $version, $isCurrent, $deletedAt] = $row;

            return [
                'id' => $id,
                'document_group_id' => $groupId,
                'agency_id' => $agencyId,
                'document_type' => $type,
                'document_number' => $number,
                'uploaded_at' => $uploadedAt,
                'expiry_date' => $expiryDate,
                'file_name' => $fileName,
                'file_extension' => pathinfo($fileName, PATHINFO_EXTENSION) ?: 'pdf',
                'version' => $version,
                'is_current' => $isCurrent,
                'description' => null,
                'deleted_at' => $deletedAt,
            ];
        })->all();
    }

    public static function referenceDate(): Carbon
    {
        return Carbon::parse('2026-07-07');
    }

    public static function resolveStatus(string $expiryDate, ?Carbon $today = null): string
    {
        $today = $today ?? self::referenceDate();
        $expiry = Carbon::parse($expiryDate);

        if ($expiry->lt($today)) {
            return 'expired';
        }

        if ($today->diffInDays($expiry, false) <= self::EXPIRY_WARNING_DAYS) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public static function enrich(array $document): array
    {
        $agency = collect(AgencyDummyData::all())->firstWhere('id', $document['agency_id']);
        $uploadedAt = Carbon::parse($document['uploaded_at']);
        $expiryDate = Carbon::parse($document['expiry_date']);
        $today = self::referenceDate();
        $status = self::resolveStatus($document['expiry_date'], $today);
        $daysRemaining = (int) $today->diffInDays($expiryDate, false);

        return array_merge($document, [
            'uuid' => 'adoc-'.str_pad((string) $document['id'], 3, '0', STR_PAD_LEFT),
            'agency_name' => $agency['company_name'] ?? '—',
            'agency_city' => $agency['city'] ?? '—',
            'agency_phone' => $agency['phone'] ?? '—',
            'agency_email' => $agency['email'] ?? '—',
            'agency_authorized' => $agency['authorized_person'] ?? '—',
            'document_type_label' => self::documentTypes()[$document['document_type']] ?? 'Diğer',
            'status' => $status,
            'status_label' => self::statuses()[$status] ?? $status,
            'uploaded_at_formatted' => $uploadedAt->format('d.m.Y'),
            'expiry_date_formatted' => $expiryDate->format('d.m.Y'),
            'days_remaining' => $daysRemaining,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function versionHistory(string $groupId): array
    {
        return collect(self::raw())
            ->filter(fn (array $row) => $row['document_group_id'] === $groupId && $row['deleted_at'] === null)
            ->map(function (array $row) {
                $uploadedAt = Carbon::parse($row['uploaded_at']);
                $expiryDate = Carbon::parse($row['expiry_date']);

                return [
                    'id' => $row['id'],
                    'version' => $row['version'],
                    'is_current' => $row['is_current'],
                    'file_name' => $row['file_name'],
                    'uploaded_at_formatted' => $uploadedAt->format('d.m.Y'),
                    'expiry_date_formatted' => $expiryDate->format('d.m.Y'),
                    'status' => self::resolveStatus($row['expiry_date']),
                ];
            })
            ->sortByDesc('version')
            ->values()
            ->all();
    }

    public static function find(int $id, bool $withTrashed = false): ?array
    {
        foreach (self::all($withTrashed, true) as $document) {
            if ($document['id'] === $id) {
                return array_merge($document, [
                    'version_history' => self::versionHistory($document['document_group_id']),
                ]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public static function summary(array $filters = []): array
    {
        $items = self::filter($filters);

        return [
            'total' => count($items),
            'valid' => collect($items)->where('status', 'valid')->count(),
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
        $today = self::referenceDate();

        return collect(self::all())
            ->filter(function (array $document) use ($filters, $today) {
                if (! empty($filters['agency_id']) && $filters['agency_id'] !== 'all') {
                    if ((int) $document['agency_id'] !== (int) $filters['agency_id']) {
                        return false;
                    }
                }

                if (! empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $document['agency_name'],
                        $document['document_number'],
                        $document['document_type_label'],
                    ]));

                    if (! str_contains($haystack, $search)) {
                        return false;
                    }
                }

                if (! empty($filters['document_type']) && $filters['document_type'] !== 'all') {
                    if ($document['document_type'] !== $filters['document_type']) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($document['status'] !== $filters['status']) {
                        return false;
                    }
                }

                if (! empty($filters['expiry_filter']) && $filters['expiry_filter'] !== 'all') {
                    $expiry = Carbon::parse($document['expiry_date']);

                    $matches = match ($filters['expiry_filter']) {
                        'expiring_soon' => $document['status'] === 'expiring_soon',
                        'expired' => $document['status'] === 'expired',
                        'this_month' => $expiry->isSameMonth($today),
                        'next_3_months' => $expiry->gte($today) && $expiry->lte($today->copy()->addMonths(3)),
                        default => true,
                    };

                    if (! $matches) {
                        return false;
                    }
                }

                return true;
            })
            ->values()
            ->all();
    }
}
