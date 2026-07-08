<?php

namespace App\Modules\Courier\Data;

use App\Support\DemoData;
use Carbon\Carbon;

class CourierVehicleDummyData
{
    public const INSURANCE_WARNING_DAYS = 30;

    /**
     * @return array<string, string>
     */
    public static function vehicleTypes(): array
    {
        return CourierDummyData::vehicleTypes();
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Pasif',
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
     * @return array<int, string>
     */
    public static function brands(): array
    {
        return collect(self::raw())
            ->pluck('brand')
            ->filter()
            ->unique()
            ->sort()
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

return collect(self::raw())
            ->map(fn (array $row) => self::enrich($row))
            ->sortByDesc(fn ($v) => sprintf('%d-%03d', $v['status'] === 'active' ? 1 : 0, $v['id']))
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

return [
            ['id' => 1, 'courier_id' => 1, 'vehicle_type' => 'motorcycle', 'plate' => '34 AY 1001', 'brand' => 'Honda', 'model' => 'Activa S', 'model_year' => 2022, 'color' => 'Kırmızı', 'license_number' => 'RUH-34-AY-1001', 'insurance_policy_number' => 'SIG-2026-001', 'insurance_expiry_date' => '2026-12-31', 'status' => 'active', 'registered_at' => '2022-03-15', 'notes' => 'Güncel aktif araç.'],
            ['id' => 2, 'courier_id' => 1, 'vehicle_type' => 'motorcycle', 'plate' => '34 AY 4521', 'brand' => 'Yamaha', 'model' => 'NMAX 155', 'model_year' => 2019, 'color' => 'Siyah', 'license_number' => 'RUH-34-AY-4521', 'insurance_policy_number' => 'SIG-2020-118', 'insurance_expiry_date' => '2021-06-30', 'status' => 'inactive', 'registered_at' => '2019-08-01', 'notes' => 'Eski araç — pasife alındı.'],
            ['id' => 3, 'courier_id' => 2, 'vehicle_type' => 'motorcycle', 'plate' => '34 MK 2208', 'brand' => 'Kuba', 'model' => 'Blueberry 125', 'model_year' => 2023, 'color' => 'Mavi', 'license_number' => 'RUH-34-MK-2208', 'insurance_policy_number' => 'SIG-2026-014', 'insurance_expiry_date' => '2026-07-25', 'status' => 'active', 'registered_at' => '2023-05-10', 'notes' => null],
            ['id' => 4, 'courier_id' => 2, 'vehicle_type' => 'motorcycle', 'plate' => '34 MK 1102', 'brand' => 'Mondial', 'model' => 'Drift 200', 'model_year' => 2018, 'color' => 'Gri', 'license_number' => 'RUH-34-MK-1102', 'insurance_policy_number' => 'SIG-2019-044', 'insurance_expiry_date' => '2020-12-31', 'status' => 'inactive', 'registered_at' => '2018-02-20', 'notes' => 'Araç değişikliği sonrası pasif.'],
            ['id' => 5, 'courier_id' => 3, 'vehicle_type' => 'motorcycle', 'plate' => '34 ED 3301', 'brand' => 'Mondial', 'model' => 'MH 200', 'model_year' => 2021, 'color' => 'Beyaz', 'license_number' => 'RUH-34-ED-3301', 'insurance_policy_number' => 'SIG-2026-022', 'insurance_expiry_date' => '2027-01-15', 'status' => 'active', 'registered_at' => '2021-11-05', 'notes' => null],
            ['id' => 6, 'courier_id' => 4, 'vehicle_type' => 'car', 'plate' => '34 SO 7788', 'brand' => 'Renault', 'model' => 'Clio', 'model_year' => 2020, 'color' => 'Gümüş', 'license_number' => 'RUH-34-SO-7788', 'insurance_policy_number' => 'SIG-2025-088', 'insurance_expiry_date' => '2026-05-01', 'status' => 'active', 'registered_at' => '2020-09-12', 'notes' => 'Sigorta yenilenmeli.'],
            ['id' => 7, 'courier_id' => 4, 'vehicle_type' => 'motorcycle', 'plate' => '34 SO 1204', 'brand' => 'Honda', 'model' => 'PCX 125', 'model_year' => 2017, 'color' => 'Siyah', 'license_number' => 'RUH-34-SO-1204', 'insurance_policy_number' => 'SIG-2018-201', 'insurance_expiry_date' => '2019-03-31', 'status' => 'inactive', 'registered_at' => '2017-04-18', 'notes' => 'Otomobile geçiş öncesi.'],
            ['id' => 8, 'courier_id' => 5, 'vehicle_type' => 'motorcycle', 'plate' => '34 VA 5566', 'brand' => 'Honda', 'model' => 'Dio', 'model_year' => 2024, 'color' => 'Beyaz', 'license_number' => 'RUH-34-VA-5566', 'insurance_policy_number' => 'SIG-2026-031', 'insurance_expiry_date' => '2026-11-30', 'status' => 'active', 'registered_at' => '2024-01-20', 'notes' => null],
            ['id' => 9, 'courier_id' => 6, 'vehicle_type' => 'ebike', 'plate' => '34 BS 9901', 'brand' => 'Kuba', 'model' => 'E-Smart 500', 'model_year' => 2023, 'color' => 'Siyah', 'license_number' => null, 'insurance_policy_number' => 'SIG-2026-045', 'insurance_expiry_date' => '2026-09-15', 'status' => 'active', 'registered_at' => '2023-07-01', 'notes' => 'Ruhsat eksik — takip ediliyor.'],
            ['id' => 10, 'courier_id' => 7, 'vehicle_type' => 'motorcycle', 'plate' => '34 CA 4412', 'brand' => 'Yamaha', 'model' => 'Aerox 155', 'model_year' => 2020, 'color' => 'Mavi', 'license_number' => 'RUH-34-CA-4412', 'insurance_policy_number' => 'SIG-2024-067', 'insurance_expiry_date' => '2025-12-31', 'status' => 'inactive', 'registered_at' => '2020-06-15', 'notes' => 'İzinli dönemde pasife alındı.'],
            ['id' => 11, 'courier_id' => 8, 'vehicle_type' => 'motorcycle', 'plate' => '34 DP 8821', 'brand' => 'Kuba', 'model' => 'Çita 200', 'model_year' => 2022, 'color' => 'Kırmızı', 'license_number' => 'RUH-34-DP-8821', 'insurance_policy_number' => 'SIG-2026-052', 'insurance_expiry_date' => '2026-07-28', 'status' => 'active', 'registered_at' => '2022-10-08', 'notes' => null],
            ['id' => 12, 'courier_id' => 9, 'vehicle_type' => 'ebike', 'plate' => '34 EY 3300', 'brand' => 'Volta', 'model' => 'VB2', 'model_year' => 2024, 'color' => 'Yeşil', 'license_number' => 'RUH-34-EY-3300', 'insurance_policy_number' => 'SIG-2026-058', 'insurance_expiry_date' => '2027-02-28', 'status' => 'active', 'registered_at' => '2024-05-22', 'notes' => null],
            ['id' => 13, 'courier_id' => 10, 'vehicle_type' => 'motorcycle', 'plate' => '34 FG 7710', 'brand' => 'Mondial', 'model' => 'MH 125', 'model_year' => 2021, 'color' => 'Siyah', 'license_number' => 'RUH-34-FG-7710', 'insurance_policy_number' => 'SIG-2025-091', 'insurance_expiry_date' => '2026-04-15', 'status' => 'active', 'registered_at' => '2021-03-30', 'notes' => 'Sigorta süresi dolmuş.'],
            ['id' => 14, 'courier_id' => 11, 'vehicle_type' => 'car', 'plate' => '34 GT 5500', 'brand' => 'Toyota', 'model' => 'Corolla', 'model_year' => 2019, 'color' => 'Beyaz', 'license_number' => 'RUH-34-GT-5500', 'insurance_policy_number' => 'SIG-2026-063', 'insurance_expiry_date' => '2027-03-01', 'status' => 'active', 'registered_at' => '2019-12-01', 'notes' => null],
            ['id' => 15, 'courier_id' => 12, 'vehicle_type' => 'motorcycle', 'plate' => '34 HK 6612', 'brand' => 'Honda', 'model' => 'Spacy 110', 'model_year' => 2023, 'color' => 'Gri', 'license_number' => 'RUH-34-HK-6612', 'insurance_policy_number' => 'SIG-2026-071', 'insurance_expiry_date' => '2026-10-20', 'status' => 'active', 'registered_at' => '2023-02-14', 'notes' => null],
            ['id' => 16, 'courier_id' => 13, 'vehicle_type' => 'bicycle', 'plate' => null, 'brand' => 'Giant', 'model' => 'Escape 3', 'model_year' => 2023, 'color' => 'Mavi', 'license_number' => null, 'insurance_policy_number' => null, 'insurance_expiry_date' => null, 'status' => 'active', 'registered_at' => '2023-09-01', 'notes' => 'Yaya teslimat bisikleti.'],
            ['id' => 17, 'courier_id' => 14, 'vehicle_type' => 'motorcycle', 'plate' => '34 KA 2299', 'brand' => 'Yamaha', 'model' => 'Fascino 125', 'model_year' => 2018, 'color' => 'Beyaz', 'license_number' => 'RUH-34-KA-2299', 'insurance_policy_number' => 'SIG-2023-112', 'insurance_expiry_date' => '2024-08-31', 'status' => 'inactive', 'registered_at' => '2018-07-20', 'notes' => 'Pasif kurye aracı.'],
            ['id' => 18, 'courier_id' => 15, 'vehicle_type' => 'car', 'plate' => '34 LS 8844', 'brand' => 'Fiat', 'model' => 'Egea', 'model_year' => 2021, 'color' => 'Lacivert', 'license_number' => 'RUH-34-LS-8844', 'insurance_policy_number' => 'SIG-2026-077', 'insurance_expiry_date' => '2026-08-10', 'status' => 'active', 'registered_at' => '2021-06-18', 'notes' => null],
            ['id' => 19, 'courier_id' => 16, 'vehicle_type' => 'motorcycle', 'plate' => '34 MK 9933', 'brand' => 'Kuba', 'model' => 'Blueberry 125', 'model_year' => 2024, 'color' => 'Turuncu', 'license_number' => 'RUH-34-MK-9933', 'insurance_policy_number' => 'SIG-2026-082', 'insurance_expiry_date' => '2027-04-30', 'status' => 'active', 'registered_at' => '2024-08-05', 'notes' => null],
            ['id' => 20, 'courier_id' => 17, 'vehicle_type' => 'ebike', 'plate' => '34 OY 4400', 'brand' => 'Volta', 'model' => 'VM4', 'model_year' => 2022, 'color' => 'Siyah', 'license_number' => 'RUH-34-OY-4400', 'insurance_policy_number' => 'SIG-2025-099', 'insurance_expiry_date' => '2026-03-15', 'status' => 'inactive', 'registered_at' => '2022-11-10', 'notes' => 'İzinli dönem.'],
            ['id' => 21, 'courier_id' => 18, 'vehicle_type' => 'motorcycle', 'plate' => '34 OB 1120', 'brand' => 'Mondial', 'model' => 'Drift 125', 'model_year' => 2020, 'color' => 'Kırmızı', 'license_number' => null, 'insurance_policy_number' => 'SIG-2026-088', 'insurance_expiry_date' => '2026-12-15', 'status' => 'active', 'registered_at' => '2020-04-25', 'notes' => 'Ruhsat belgesi eksik.'],
            ['id' => 22, 'courier_id' => 19, 'vehicle_type' => 'motorcycle', 'plate' => '34 RO 5560', 'brand' => 'Honda', 'model' => 'Activa 6G', 'model_year' => 2023, 'color' => 'Gri', 'license_number' => 'RUH-34-RO-5560', 'insurance_policy_number' => 'SIG-2026-095', 'insurance_expiry_date' => '2027-01-31', 'status' => 'active', 'registered_at' => '2023-03-12', 'notes' => null],
            ['id' => 23, 'courier_id' => 20, 'vehicle_type' => 'pedestrian', 'plate' => null, 'brand' => null, 'model' => null, 'model_year' => null, 'color' => null, 'license_number' => null, 'insurance_policy_number' => null, 'insurance_expiry_date' => null, 'status' => 'active', 'registered_at' => '2025-06-01', 'notes' => 'Yaya kurye — araç kullanılmıyor.'],
            ['id' => 24, 'courier_id' => 21, 'vehicle_type' => 'bicycle', 'plate' => null, 'brand' => 'Bianchi', 'model' => 'Impulso', 'model_year' => 2022, 'color' => 'Yeşil', 'license_number' => null, 'insurance_policy_number' => null, 'insurance_expiry_date' => null, 'status' => 'active', 'registered_at' => '2022-08-15', 'notes' => null],
            ['id' => 25, 'courier_id' => 22, 'vehicle_type' => 'motorcycle', 'plate' => '34 UK 7701', 'brand' => 'Yamaha', 'model' => 'NMAX 155', 'model_year' => 2024, 'color' => 'Siyah', 'license_number' => 'RUH-34-UK-7701', 'insurance_policy_number' => 'SIG-2026-101', 'insurance_expiry_date' => '2027-05-20', 'status' => 'active', 'registered_at' => '2024-11-01', 'notes' => null],
            ['id' => 26, 'courier_id' => 23, 'vehicle_type' => 'car', 'plate' => '34 YD 3308', 'brand' => 'Renault', 'model' => 'Symbol', 'model_year' => 2017, 'color' => 'Beyaz', 'license_number' => 'RUH-34-YD-3308', 'insurance_policy_number' => 'SIG-2024-130', 'insurance_expiry_date' => '2025-09-30', 'status' => 'inactive', 'registered_at' => '2017-05-20', 'notes' => 'Pasif kurye.'],
            ['id' => 27, 'courier_id' => 24, 'vehicle_type' => 'motorcycle', 'plate' => '34 ZI 9012', 'brand' => 'Kuba', 'model' => 'Çita 200', 'model_year' => 2023, 'color' => 'Mavi', 'license_number' => 'RUH-34-ZI-9012', 'insurance_policy_number' => 'SIG-2026-108', 'insurance_expiry_date' => '2026-07-30', 'status' => 'active', 'registered_at' => '2023-12-08', 'notes' => null],
            ['id' => 28, 'courier_id' => 25, 'vehicle_type' => 'ebike', 'plate' => '34 BT 4455', 'brand' => 'Kuba', 'model' => 'E-Smart 500', 'model_year' => 2025, 'color' => 'Beyaz', 'license_number' => 'RUH-34-BT-4455', 'insurance_policy_number' => 'SIG-2026-115', 'insurance_expiry_date' => '2027-06-01', 'status' => 'active', 'registered_at' => '2025-10-12', 'notes' => null],
            ['id' => 29, 'courier_id' => 26, 'vehicle_type' => 'motorcycle', 'plate' => '34 CB 6677', 'brand' => 'Mondial', 'model' => 'MH 125', 'model_year' => 2019, 'color' => 'Gri', 'license_number' => 'RUH-34-CB-6677', 'insurance_policy_number' => 'SIG-2024-140', 'insurance_expiry_date' => '2025-11-30', 'status' => 'inactive', 'registered_at' => '2019-09-05', 'notes' => 'İzinli dönem.'],
            ['id' => 30, 'courier_id' => 27, 'vehicle_type' => 'motorcycle', 'plate' => '34 DS 2288', 'brand' => 'Honda', 'model' => 'Dio', 'model_year' => 2022, 'color' => 'Kırmızı', 'license_number' => 'RUH-34-DS-2288', 'insurance_policy_number' => 'SIG-2026-120', 'insurance_expiry_date' => '2026-11-15', 'status' => 'active', 'registered_at' => '2022-04-18', 'notes' => null],
            ['id' => 31, 'courier_id' => 28, 'vehicle_type' => 'bicycle', 'plate' => null, 'brand' => 'Giant', 'model' => 'Talon 2', 'model_year' => 2024, 'color' => 'Siyah', 'license_number' => null, 'insurance_policy_number' => null, 'insurance_expiry_date' => null, 'status' => 'active', 'registered_at' => '2024-02-01', 'notes' => null],
            ['id' => 32, 'courier_id' => 29, 'vehicle_type' => 'car', 'plate' => '34 FG 9900', 'brand' => 'Renault', 'model' => 'Megane', 'model_year' => 2020, 'color' => 'Gri', 'license_number' => 'RUH-34-FG-9900', 'insurance_policy_number' => 'SIG-2026-125', 'insurance_expiry_date' => '2026-07-22', 'status' => 'active', 'registered_at' => '2020-07-30', 'notes' => null],
            ['id' => 33, 'courier_id' => 30, 'vehicle_type' => 'pedestrian', 'plate' => null, 'brand' => null, 'model' => null, 'model_year' => null, 'color' => null, 'license_number' => null, 'insurance_policy_number' => null, 'insurance_expiry_date' => null, 'status' => 'inactive', 'registered_at' => '2024-01-10', 'notes' => 'Pasif yaya kurye kaydı.'],
            ['id' => 34, 'courier_id' => 31, 'vehicle_type' => 'ebike', 'plate' => '34 IM 5511', 'brand' => 'Volta', 'model' => 'VB2', 'model_year' => 2025, 'color' => 'Mavi', 'license_number' => 'RUH-34-IM-5511', 'insurance_policy_number' => 'SIG-2026-132', 'insurance_expiry_date' => '2027-07-01', 'status' => 'active', 'registered_at' => '2025-08-20', 'notes' => null],
            ['id' => 35, 'courier_id' => 32, 'vehicle_type' => 'motorcycle', 'plate' => '34 KA 7780', 'brand' => 'Yamaha', 'model' => 'Aerox 155', 'model_year' => 2023, 'color' => 'Beyaz', 'license_number' => 'RUH-34-KA-7780', 'insurance_policy_number' => 'SIG-2026-138', 'insurance_expiry_date' => '2027-02-15', 'status' => 'active', 'registered_at' => '2023-10-25', 'notes' => null],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function enrich(array $row): array
    {
        $today = Carbon::today();
        $courier = collect(CourierDummyData::all())->firstWhere('id', $row['courier_id']);
        $vehicleType = $row['vehicle_type'];
        $requiresVehicleDocs = $vehicleType !== 'pedestrian';

        $licenseStatus = $requiresVehicleDocs
            ? (! empty($row['license_number']) ? 'valid' : 'missing')
            : null;

        $insuranceStatus = null;
        $insuranceExpiryFormatted = '—';

        if ($requiresVehicleDocs && ! empty($row['insurance_expiry_date'])) {
            $expiry = Carbon::parse($row['insurance_expiry_date']);
            $insuranceExpiryFormatted = $expiry->format('d.m.Y');
            $daysRemaining = $today->diffInDays($expiry, false);

            if ($daysRemaining < 0) {
                $insuranceStatus = 'expired';
            } elseif ($daysRemaining <= self::INSURANCE_WARNING_DAYS) {
                $insuranceStatus = 'expiring_soon';
            } else {
                $insuranceStatus = 'valid';
            }
        } elseif ($requiresVehicleDocs && empty($row['insurance_expiry_date'])) {
            $insuranceStatus = 'missing';
        }

        $registeredAt = Carbon::parse($row['registered_at']);

        return array_merge($row, [
            'uuid' => 'cveh-'.str_pad((string) $row['id'], 3, '0', STR_PAD_LEFT),
            'courier_name' => $courier['full_name'] ?? '—',
            'courier_phone' => $courier['phone'] ?? '—',
            'courier_type' => $courier['courier_type'] ?? 'independent',
            'vehicle_type_label' => self::vehicleTypes()[$vehicleType] ?? '—',
            'plate_formatted' => $row['plate'] ?? '—',
            'model_year_formatted' => $row['model_year'] ? (string) $row['model_year'] : '—',
            'brand_formatted' => $row['brand'] ?? '—',
            'model_formatted' => $row['model'] ?? '—',
            'color_formatted' => $row['color'] ?? '—',
            'license_status' => $licenseStatus,
            'insurance_status' => $insuranceStatus,
            'insurance_expiry_formatted' => $insuranceExpiryFormatted,
            'status_label' => self::statuses()[$row['status']] ?? '—',
            'registered_at_formatted' => $registeredAt->format('d.m.Y'),
            'requires_vehicle_docs' => $requiresVehicleDocs,
            'history' => self::buildHistory($row),
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array<string, string>>
     */
    private static function buildHistory(array $row): array
    {
        $events = [
            [
                'date' => $row['registered_at'],
                'date_formatted' => Carbon::parse($row['registered_at'])->format('d.m.Y'),
                'label' => 'Araç kaydedildi',
                'detail' => self::vehicleTypes()[$row['vehicle_type']].' — '.($row['plate'] ?? 'Plakasız'),
            ],
        ];

        if (! empty($row['insurance_policy_number']) && ! empty($row['insurance_expiry_date'])) {
            $events[] = [
                'date' => Carbon::parse($row['insurance_expiry_date'])->subYear()->format('Y-m-d'),
                'date_formatted' => Carbon::parse($row['insurance_expiry_date'])->subYear()->format('d.m.Y'),
                'label' => 'Sigorta poliçesi tanımlandı',
                'detail' => $row['insurance_policy_number'],
            ];
        }

        if ($row['status'] === 'inactive') {
            $events[] = [
                'date' => Carbon::parse($row['registered_at'])->addMonths(6)->format('Y-m-d'),
                'date_formatted' => Carbon::parse($row['registered_at'])->addMonths(6)->format('d.m.Y'),
                'label' => 'Araç pasife alındı',
                'detail' => 'Kayıt silinmedi — geçmiş korunuyor.',
            ];
        }

        return collect($events)
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function courierVehicleHistory(int $courierId): array
    {
        return collect(self::all())
            ->where('courier_id', $courierId)
            ->sortByDesc('registered_at')
            ->values()
            ->all();
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $vehicle) {
            if ($vehicle['id'] === $id) {
                return $vehicle;
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
        return [
            'count' => count($items),
            'motorcycle' => collect($items)->where('vehicle_type', 'motorcycle')->count(),
            'car' => collect($items)->where('vehicle_type', 'car')->count(),
            'active' => collect($items)->where('status', 'active')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        return collect(self::all())
            ->filter(function (array $vehicle) use ($filters) {
                if (! empty($filters['courier_id']) && $filters['courier_id'] !== 'all') {
                    if ((int) $vehicle['courier_id'] !== (int) $filters['courier_id']) {
                        return false;
                    }
                }

                if (! empty($filters['vehicle_type']) && $filters['vehicle_type'] !== 'all') {
                    if ($vehicle['vehicle_type'] !== $filters['vehicle_type']) {
                        return false;
                    }
                }

                if (! empty($filters['brand']) && $filters['brand'] !== 'all') {
                    if ($vehicle['brand'] !== $filters['brand']) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($vehicle['status'] !== $filters['status']) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(fn ($v) => sprintf('%d-%03d', $v['status'] === 'active' ? 1 : 0, $v['id']))
            ->values()
            ->all();
    }
}
