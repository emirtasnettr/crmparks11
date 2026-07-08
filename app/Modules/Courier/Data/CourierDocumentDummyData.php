<?php

namespace App\Modules\Courier\Data;

use App\Support\DemoData;
use Carbon\Carbon;

class CourierDocumentDummyData
{
    public const EXPIRY_WARNING_DAYS = 30;

    /**
     * @return array<string, string>
     */
    public static function documentTypes(): array
    {
        return [
            'identity' => 'Kimlik',
            'driving_license' => 'Ehliyet',
            'tax_plate' => 'Vergi Levhası',
            'activity_certificate' => 'Faaliyet Belgesi',
            'license' => 'Ruhsat',
            'insurance' => 'Sigorta Poliçesi',
            'criminal_record' => 'Adli Sicil Kaydı',
            'residence' => 'İkametgah',
            'health_report' => 'Sağlık Raporu',
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
    public static function couriers(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

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
        if (! DemoData::enabled()) {
            return [];
        }

$raw = [
            [1, 'identity', 'KIM-12345678901', '2024-01-15', '2034-01-15', 'ahmet-yildiz-kimlik.pdf'],
            [1, 'driving_license', 'EHL-34-AB-1234', '2025-03-10', '2026-07-25', 'ahmet-yildiz-ehliyet.pdf'],
            [1, 'insurance', 'SIG-2026-001', '2026-01-01', '2026-12-31', 'ahmet-yildiz-sigorta.pdf'],
            [2, 'identity', 'KIM-23456789012', '2023-06-20', '2033-06-20', 'murat-kaya-kimlik.pdf'],
            [2, 'license', 'RUH-34-MK-5678', '2025-08-01', '2026-07-20', 'murat-kaya-ruhsat.pdf'],
            [2, 'health_report', 'SGR-2026-014', '2026-02-15', '2027-02-15', 'murat-kaya-saglik.pdf'],
            [3, 'driving_license', 'EHL-06-CD-9012', '2024-11-05', '2026-06-15', 'emre-demir-ehliyet.pdf'],
            [3, 'criminal_record', 'ASK-2026-088', '2026-01-20', '2026-07-15', 'emre-demir-adli-sicil.pdf'],
            [3, 'residence', 'IKM-2025-442', '2025-09-10', '2026-08-01', 'emre-demir-ikametgah.pdf'],
            [4, 'identity', 'KIM-45678901234', '2022-04-12', '2032-04-12', 'serkan-oz-kimlik.pdf'],
            [4, 'insurance', 'SIG-2025-178', '2025-06-01', '2026-06-01', 'serkan-oz-sigorta.pdf'],
            [4, 'tax_plate', 'VL-4567890123', '2025-01-10', '2026-07-10', 'serkan-oz-vergi.pdf'],
            [5, 'driving_license', 'EHL-35-VA-3456', '2023-12-18', '2026-05-30', 'volkan-arslan-ehliyet.pdf'],
            [5, 'license', 'RUH-35-VA-7890', '2024-07-22', '2026-07-28', 'volkan-arslan-ruhsat.pdf'],
            [5, 'activity_certificate', 'FB-2024-991', '2024-03-01', '2027-03-01', 'volkan-arslan-faaliyet.pdf'],
            [6, 'identity', 'KIM-67890123456', '2025-02-28', '2035-02-28', 'burak-sen-kimlik.pdf'],
            [6, 'health_report', 'SGR-2026-022', '2026-03-01', '2026-07-05', 'burak-sen-saglik.pdf'],
            [6, 'criminal_record', 'ASK-2025-301', '2025-11-15', '2026-06-20', 'burak-sen-adli-sicil.pdf'],
            [7, 'driving_license', 'EHL-35-CA-1122', '2024-05-09', '2026-04-10', 'cem-akin-ehliyet.pdf'],
            [7, 'residence', 'IKM-2026-015', '2026-01-05', '2026-07-30', 'cem-akin-ikametgah.pdf'],
            [8, 'identity', 'KIM-89012345678', '2023-08-30', '2033-08-30', 'deniz-polat-kimlik.pdf'],
            [8, 'insurance', 'SIG-2026-045', '2026-02-01', '2026-08-15', 'deniz-polat-sigorta.pdf'],
            [9, 'driving_license', 'EHL-34-EY-7788', '2025-01-20', '2026-09-01', 'efe-yalcin-ehliyet.pdf'],
            [9, 'license', 'RUH-34-EY-9900', '2025-04-14', '2026-10-20', 'efe-yalcin-ruhsat.pdf'],
            [10, 'tax_plate', 'VL-1123456789', '2024-09-01', '2026-06-01', 'furkan-gunes-vergi.pdf'],
            [10, 'activity_certificate', 'FB-2025-112', '2025-07-10', '2026-07-22', 'furkan-gunes-faaliyet.pdf'],
            [11, 'identity', 'KIM-22345678901', '2022-11-11', '2032-11-11', 'gokhan-tekin-kimlik.pdf'],
            [11, 'criminal_record', 'ASK-2026-055', '2026-04-01', '2026-07-08', 'gokhan-tekin-adli-sicil.pdf'],
            [12, 'driving_license', 'EHL-34-HK-4455', '2024-02-17', '2026-08-06', 'hakan-koc-ehliyet.pdf'],
            [12, 'health_report', 'SGR-2026-033', '2026-05-10', '2027-05-10', 'hakan-koc-saglik.pdf'],
            [13, 'residence', 'IKM-2025-778', '2025-12-01', '2026-05-15', 'ibrahim-cetin-ikametgah.pdf'],
            [13, 'other', 'BLG-2026-001', '2026-01-18', '2026-12-18', 'ibrahim-cetin-diger.pdf'],
            [14, 'identity', 'KIM-55678901234', '2021-07-07', '2031-07-07', 'kaan-aydin-kimlik.pdf'],
            [14, 'driving_license', 'EHL-34-KA-6677', '2023-03-25', '2026-03-25', 'kaan-aydin-ehliyet.pdf'],
            [15, 'insurance', 'SIG-2025-199', '2025-08-20', '2026-07-12', 'levent-sari-sigorta.pdf'],
            [15, 'license', 'RUH-06-LS-3344', '2024-10-05', '2026-11-30', 'levent-sari-ruhsat.pdf'],
            [16, 'driving_license', 'EHL-23-MK-8899', '2025-05-30', '2026-07-18', 'mert-korkmaz-ehliyet.pdf'],
            [16, 'tax_plate', 'VL-7789012345', '2025-02-14', '2026-08-20', 'mert-korkmaz-vergi.pdf'],
            [17, 'health_report', 'SGR-2025-088', '2025-10-01', '2026-06-25', 'oguz-yilmaz-saglik.pdf'],
            [17, 'criminal_record', 'ASK-2025-412', '2025-08-12', '2026-05-01', 'oguz-yilmaz-adli-sicil.pdf'],
            [18, 'identity', 'KIM-99012345678', '2023-01-22', '2033-01-22', 'onur-basaran-kimlik.pdf'],
            [18, 'driving_license', 'EHL-34-OB-2233', '2024-06-08', '2026-09-15', 'onur-basaran-ehliyet.pdf'],
            [19, 'activity_certificate', 'FB-2026-021', '2026-02-20', '2027-02-20', 'riza-ozturk-faaliyet.pdf'],
            [19, 'residence', 'IKM-2026-028', '2026-03-15', '2026-07-25', 'riza-ozturk-ikametgah.pdf'],
            [20, 'health_report', 'SGR-2026-041', '2026-04-05', '2027-04-05', 'selim-erdogan-saglik.pdf'],
            [21, 'driving_license', 'EHL-34-TU-5566', '2025-07-19', '2026-07-02', 'tolga-ucar-ehliyet.pdf'],
            [22, 'insurance', 'SIG-2026-067', '2026-01-25', '2026-07-31', 'umut-karaca-sigorta.pdf'],
            [23, 'identity', 'KIM-54567890123', '2020-09-09', '2030-09-09', 'yasin-duman-kimlik.pdf'],
            [24, 'license', 'RUH-34-ZI-7788', '2024-12-12', '2026-08-10', 'zafer-isik-ruhsat.pdf'],
            [25, 'driving_license', 'EHL-23-BT-9900', '2025-03-03', '2026-10-01', 'baris-tunc-ehliyet.pdf'],
            [26, 'criminal_record', 'ASK-2026-072', '2026-02-28', '2026-07-06', 'caner-bilgin-adli-sicil.pdf'],
            [27, 'tax_plate', 'VL-9890123456', '2025-06-18', '2026-09-30', 'dogan-sezer-vergi.pdf'],
            [28, 'residence', 'IKM-2025-901', '2025-11-20', '2026-04-20', 'erhan-vural-ikametgah.pdf'],
            [29, 'insurance', 'SIG-2026-078', '2026-03-08', '2026-07-14', 'fatih-gencer-sigorta.pdf'],
            [30, 'driving_license', 'EHL-34-HO-1122', '2022-08-08', '2026-02-08', 'halil-ozkan-ehliyet.pdf'],
            [31, 'activity_certificate', 'FB-2026-034', '2026-04-22', '2027-04-22', 'ilker-mutlu-faaliyet.pdf'],
            [32, 'health_report', 'SGR-2026-049', '2026-05-01', '2026-07-20', 'koray-aslan-saglik.pdf'],
        ];

        $couriers = collect(CourierDummyData::all())->keyBy('id');

        return collect($raw)
            ->values()
            ->map(function (array $row, int $index) use ($couriers) {
                [$courierId, $type, $number, $uploadedAt, $expiryDate, $fileName] = $row;
                $courier = $couriers->get($courierId);

                return self::enrich([
                    'id' => $index + 1,
                    'uuid' => 'cdoc-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'courier_id' => $courierId,
                    'courier_name' => $courier['full_name'] ?? '—',
                    'courier_phone' => $courier['phone'] ?? '—',
                    'courier_type' => $courier['courier_type'] ?? 'independent',
                    'courier_type_label' => $courier['courier_type_label'] ?? '—',
                    'document_type' => $type,
                    'document_number' => $number,
                    'uploaded_at' => $uploadedAt,
                    'expiry_date' => $expiryDate,
                    'file_name' => $fileName,
                    'file_extension' => pathinfo($fileName, PATHINFO_EXTENSION) ?: 'pdf',
                    'description' => null,
                ]);
            })
            ->all();
    }

    public static function resolveStatus(string $expiryDate, ?Carbon $today = null): string
    {
        $today = $today ?? Carbon::today();
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
        $uploadedAt = Carbon::parse($document['uploaded_at']);
        $expiryDate = Carbon::parse($document['expiry_date']);
        $today = Carbon::today();
        $status = self::resolveStatus($document['expiry_date'], $today);
        $daysRemaining = (int) $today->diffInDays($expiryDate, false);

        return array_merge($document, [
            'document_type_label' => self::documentTypes()[$document['document_type']] ?? 'Diğer',
            'status' => $status,
            'status_label' => self::statuses()[$status] ?? $status,
            'uploaded_at_formatted' => $uploadedAt->format('d.m.Y'),
            'expiry_date_formatted' => $expiryDate->format('d.m.Y'),
            'days_remaining' => $daysRemaining,
        ]);
    }

    public static function find(int|string $id): ?array
    {
        $id = (int) $id;

        foreach (self::all() as $document) {
            if ($document['id'] === $id) {
                return $document;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public static function summary(array $filters): array
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
        $today = Carbon::today();

        return collect(self::all())
            ->filter(function (array $document) use ($filters, $today) {
                if (! empty($filters['courier_id']) && $filters['courier_id'] !== 'all') {
                    if ((int) $document['courier_id'] !== (int) $filters['courier_id']) {
                        return false;
                    }
                }

                if (! empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $document['courier_name'],
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
