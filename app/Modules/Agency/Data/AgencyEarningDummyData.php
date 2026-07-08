<?php

namespace App\Modules\Agency\Data;

use App\Support\DemoData;
use App\Core\Helpers\MoneyCalculator;
use Carbon\Carbon;

class AgencyEarningDummyData
{
    /**
     * @return array<string, string>
     */
    public static function earningStatuses(): array
    {
        return [
            'draft' => 'Taslak',
            'pending' => 'Bekliyor',
            'approved' => 'Onaylandı',
            'paid' => 'Ödendi',
            'cancelled' => 'İptal',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paymentStatuses(): array
    {
        return [
            'pending' => 'Bekliyor',
            'paid' => 'Ödendi',
            'partial' => 'Kısmi Ödendi',
            'cancelled' => 'İptal',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function periodTypes(): array
    {
        return [
            'monthly' => 'Aylık',
            'weekly' => 'Haftalık',
            'custom' => 'Özel Dönem',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function months(): array
    {
        return [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
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
    public static function all(bool $withTrashed = false): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(self::raw())
            ->filter(fn (array $row) => $withTrashed || $row['deleted_at'] === null)
            ->map(fn (array $row) => self::enrich($row))
            ->sortByDesc(fn ($e) => sprintf('%04d-%02d', $e['period_year'], $e['period_month']))
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
            ['id' => 1, 'reference' => 'AHK-2026-001', 'agency_id' => 1, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 5, 'package_count' => 12400, 'gross_amount' => 62000, 'extra_payment' => 1500, 'deduction' => 800, 'payment_status' => 'paid', 'payment_date' => '2026-07-05', 'paid_amount' => 62700, 'status' => 'paid', 'description' => 'Haziran aylık komisyon hakedişi', 'deleted_at' => null],
            ['id' => 2, 'reference' => 'AHK-2026-002', 'agency_id' => 2, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 5, 'package_count' => 9800, 'gross_amount' => 49000, 'extra_payment' => 0, 'deduction' => 500, 'payment_status' => 'paid', 'payment_date' => '2026-07-04', 'paid_amount' => 48500, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 3, 'reference' => 'AHK-2026-003', 'agency_id' => 3, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 4, 'package_count' => 7200, 'gross_amount' => 36000, 'extra_payment' => 2000, 'deduction' => 1200, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'approved', 'description' => 'Onay bekleyen ödeme', 'deleted_at' => null],
            ['id' => 4, 'reference' => 'AHK-2026-004', 'agency_id' => 4, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 12, 'package_count' => 28500, 'gross_amount' => 142500, 'extra_payment' => 5000, 'deduction' => 3200, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'approved', 'description' => 'Anadolu yoğun dönem', 'deleted_at' => null],
            ['id' => 5, 'reference' => 'AHK-2026-005', 'agency_id' => 5, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 8, 'package_count' => 15200, 'gross_amount' => 76000, 'extra_payment' => 0, 'deduction' => 1500, 'payment_status' => 'paid', 'payment_date' => '2026-07-02', 'paid_amount' => 74500, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 6, 'reference' => 'AHK-2026-006', 'agency_id' => 1, 'period_month' => 5, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 5, 'package_count' => 11800, 'gross_amount' => 59000, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-06-08', 'paid_amount' => 59000, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 7, 'reference' => 'AHK-2026-007', 'agency_id' => 2, 'period_month' => 5, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 5, 'package_count' => 9200, 'gross_amount' => 46000, 'extra_payment' => 800, 'deduction' => 300, 'payment_status' => 'paid', 'payment_date' => '2026-06-10', 'paid_amount' => 46500, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 8, 'reference' => 'AHK-2026-008', 'agency_id' => 7, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 9, 'package_count' => 16800, 'gross_amount' => 84000, 'extra_payment' => 1200, 'deduction' => 0, 'payment_status' => 'partial', 'payment_date' => '2026-06-28', 'paid_amount' => 42000, 'status' => 'approved', 'description' => 'Kısmi ödeme yapıldı', 'deleted_at' => null],
            ['id' => 9, 'reference' => 'AHK-2026-009', 'agency_id' => 10, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 6, 'package_count' => 8900, 'gross_amount' => 44500, 'extra_payment' => 0, 'deduction' => 250, 'payment_status' => 'paid', 'payment_date' => '2026-07-01', 'paid_amount' => 44250, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 10, 'reference' => 'AHK-2026-010', 'agency_id' => 11, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 11, 'package_count' => 22400, 'gross_amount' => 112000, 'extra_payment' => 3000, 'deduction' => 1800, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'pending', 'description' => 'Onay sürecinde', 'deleted_at' => null],
            ['id' => 11, 'reference' => 'AHK-2026-011', 'agency_id' => 16, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 14, 'package_count' => 31200, 'gross_amount' => 156000, 'extra_payment' => 8000, 'deduction' => 4500, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'approved', 'description' => 'Turizm sezonu', 'deleted_at' => null],
            ['id' => 12, 'reference' => 'AHK-2026-012', 'agency_id' => 17, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 18, 'package_count' => 38900, 'gross_amount' => 194500, 'extra_payment' => 0, 'deduction' => 6200, 'payment_status' => 'paid', 'payment_date' => '2026-07-06', 'paid_amount' => 188300, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 13, 'reference' => 'AHK-2026-013', 'agency_id' => 25, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 22, 'package_count' => 42500, 'gross_amount' => 212500, 'extra_payment' => 10000, 'deduction' => 5500, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'approved', 'description' => 'Ege bölgesi Haziran', 'deleted_at' => null],
            ['id' => 14, 'reference' => 'AHK-2026-014', 'agency_id' => 1, 'period_month' => 7, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 5, 'package_count' => 6200, 'gross_amount' => 31000, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'pending', 'description' => 'Temmuz devam ediyor', 'deleted_at' => null],
            ['id' => 15, 'reference' => 'AHK-2026-015', 'agency_id' => 2, 'period_month' => 7, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 5, 'package_count' => 5800, 'gross_amount' => 29000, 'extra_payment' => 500, 'deduction' => 200, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'draft', 'description' => 'Taslak kayıt', 'deleted_at' => null],
            ['id' => 16, 'reference' => 'AHK-2026-016', 'agency_id' => 3, 'period_month' => 7, 'period_year' => 2026, 'period_type' => 'weekly', 'period_custom_label' => '1–7 Temmuz 2026', 'courier_count' => 4, 'package_count' => 1850, 'gross_amount' => 9250, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'pending', 'description' => 'Haftalık hakediş', 'deleted_at' => null],
            ['id' => 17, 'reference' => 'AHK-2026-017', 'agency_id' => 4, 'period_month' => 7, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 12, 'package_count' => 14200, 'gross_amount' => 71000, 'extra_payment' => 2500, 'deduction' => 1100, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'approved', 'description' => null, 'deleted_at' => null],
            ['id' => 18, 'reference' => 'AHK-2026-018', 'agency_id' => 8, 'period_month' => 5, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 7, 'package_count' => 11200, 'gross_amount' => 56000, 'extra_payment' => 0, 'deduction' => 800, 'payment_status' => 'paid', 'payment_date' => '2026-06-12', 'paid_amount' => 55200, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 19, 'reference' => 'AHK-2026-019', 'agency_id' => 13, 'period_month' => 5, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 4, 'package_count' => 4800, 'gross_amount' => 24000, 'extra_payment' => 600, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-06-15', 'paid_amount' => 24600, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 20, 'reference' => 'AHK-2026-020', 'agency_id' => 14, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 5, 'package_count' => 7600, 'gross_amount' => 38000, 'extra_payment' => 0, 'deduction' => 500, 'payment_status' => 'cancelled', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'cancelled', 'description' => 'İptal — yeniden hesaplanacak', 'deleted_at' => null],
            ['id' => 21, 'reference' => 'AHK-2026-021', 'agency_id' => 20, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 10, 'package_count' => 19800, 'gross_amount' => 99000, 'extra_payment' => 2000, 'deduction' => 1500, 'payment_status' => 'paid', 'payment_date' => '2026-07-03', 'paid_amount' => 99500, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 22, 'reference' => 'AHK-2026-022', 'agency_id' => 21, 'period_month' => 4, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 6, 'package_count' => 9200, 'gross_amount' => 46000, 'extra_payment' => 0, 'deduction' => 400, 'payment_status' => 'paid', 'payment_date' => '2026-05-10', 'paid_amount' => 45600, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 23, 'reference' => 'AHK-2026-023', 'agency_id' => 22, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 4, 'package_count' => 5400, 'gross_amount' => 27000, 'extra_payment' => 500, 'deduction' => 0, 'payment_status' => 'partial', 'payment_date' => '2026-06-30', 'paid_amount' => 15000, 'status' => 'approved', 'description' => 'Kısmi ödeme', 'deleted_at' => null],
            ['id' => 24, 'reference' => 'AHK-2026-024', 'agency_id' => 6, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'custom', 'period_custom_label' => '15 Haz – 30 Haz 2026', 'courier_count' => 3, 'package_count' => 2100, 'gross_amount' => 10500, 'extra_payment' => 0, 'deduction' => 200, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'pending', 'description' => 'Özel dönem pilot', 'deleted_at' => null],
            ['id' => 25, 'reference' => 'AHK-2026-025', 'agency_id' => 12, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 2, 'package_count' => 3200, 'gross_amount' => 16000, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'draft', 'description' => 'Beklemedeki acente', 'deleted_at' => null],
            ['id' => 26, 'reference' => 'AHK-2026-026', 'agency_id' => 15, 'period_month' => 5, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 3, 'package_count' => 4500, 'gross_amount' => 22500, 'extra_payment' => 300, 'deduction' => 150, 'payment_status' => 'paid', 'payment_date' => '2026-06-08', 'paid_amount' => 22650, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 27, 'reference' => 'AHK-2026-027', 'agency_id' => 18, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 1, 'package_count' => 1800, 'gross_amount' => 9000, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'pending', 'description' => 'Yeni acente ilk hakediş', 'deleted_at' => null],
            ['id' => 28, 'reference' => 'AHK-2026-028', 'agency_id' => 23, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 2, 'package_count' => 2800, 'gross_amount' => 14000, 'extra_payment' => 0, 'deduction' => 100, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'approved', 'description' => null, 'deleted_at' => null],
            ['id' => 29, 'reference' => 'AHK-2026-029', 'agency_id' => 24, 'period_month' => 5, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 3, 'package_count' => 5100, 'gross_amount' => 25500, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-06-18', 'paid_amount' => 25500, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 30, 'reference' => 'AHK-2026-030', 'agency_id' => 5, 'period_month' => 7, 'period_year' => 2026, 'period_type' => 'weekly', 'period_custom_label' => '24–30 Haz 2026', 'courier_count' => 8, 'package_count' => 3200, 'gross_amount' => 16000, 'extra_payment' => 400, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-07-01', 'paid_amount' => 16400, 'status' => 'paid', 'description' => 'Haftalık kapanış', 'deleted_at' => null],
            ['id' => 31, 'reference' => 'AHK-2026-031', 'agency_id' => 11, 'period_month' => 4, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 11, 'package_count' => 19800, 'gross_amount' => 99000, 'extra_payment' => 0, 'deduction' => 2200, 'payment_status' => 'paid', 'payment_date' => '2026-05-12', 'paid_amount' => 96800, 'status' => 'paid', 'description' => null, 'deleted_at' => null],
            ['id' => 32, 'reference' => 'AHK-2026-032', 'agency_id' => 2, 'period_month' => 6, 'period_year' => 2026, 'period_type' => 'monthly', 'period_custom_label' => null, 'courier_count' => 5, 'package_count' => 9500, 'gross_amount' => 47500, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'status' => 'pending', 'description' => 'Tekrar hesaplama öncesi', 'deleted_at' => '2026-07-01'],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function enrich(array $row): array
    {
        $agency = collect(AgencyDummyData::all())->firstWhere('id', $row['agency_id']);
        $months = self::months();

        $gross = (float) $row['gross_amount'];
        $extra = (float) ($row['extra_payment'] ?? 0);
        $deduction = (float) ($row['deduction'] ?? 0);
        $netPayment = round($gross + $extra - $deduction, 2);
        $paidAmount = (float) ($row['paid_amount'] ?? 0);

        $periodLabel = $row['period_type'] === 'monthly'
            ? ($months[$row['period_month']] ?? '').' '.$row['period_year']
            : ($row['period_custom_label'] ?? ($months[$row['period_month']] ?? '').' '.$row['period_year']);

        $paymentDate = $row['payment_date']
            ? Carbon::parse($row['payment_date'])->format('d.m.Y')
            : '—';

        $extraPayments = $extra > 0
            ? [['label' => 'Ek Ödeme', 'amount' => $extra]]
            : [];

        $deductions = $deduction > 0
            ? [['label' => 'Kesinti', 'amount' => $deduction]]
            : [];

        if ($extra > 500 && count($extraPayments) === 1) {
            $extraPayments = [
                ['label' => 'Performans Primi', 'amount' => round($extra * 0.6, 2)],
                ['label' => 'Diğer Ek Ödemeler', 'amount' => round($extra * 0.4, 2)],
            ];
        }

        if ($deduction > 500 && count($deductions) === 1) {
            $deductions = [
                ['label' => 'Operasyon Kesintisi', 'amount' => round($deduction * 0.7, 2)],
                ['label' => 'Diğer Kesintiler', 'amount' => round($deduction * 0.3, 2)],
            ];
        }

        return array_merge($row, [
            'uuid' => 'aern-'.str_pad((string) $row['id'], 3, '0', STR_PAD_LEFT),
            'agency_name' => $agency['company_name'] ?? '—',
            'agency_city' => $agency['city'] ?? '—',
            'agency_phone' => $agency['phone'] ?? '—',
            'agency_email' => $agency['email'] ?? '—',
            'agency_authorized' => $agency['authorized_person'] ?? '—',
            'agency_status' => $agency['status'] ?? '—',
            'period_label' => $periodLabel,
            'period_type_label' => self::periodTypes()[$row['period_type']] ?? '—',
            'gross_amount_formatted' => MoneyCalculator::format($gross),
            'net_payment' => $netPayment,
            'net_payment_formatted' => MoneyCalculator::format($netPayment),
            'status_label' => self::earningStatuses()[$row['status']] ?? '—',
            'payment_status_label' => self::paymentStatuses()[$row['payment_status']] ?? '—',
            'payment_date_formatted' => $paymentDate,
            'remaining_payment' => max(0, round($netPayment - $paidAmount, 2)),
            'linked_couriers' => self::linkedCouriers((int) $row['agency_id'], (int) $row['courier_count']),
            'extra_payments' => $extraPayments,
            'deductions' => $deductions,
        ]);
    }

    /**
     * @return array<int, array{name: string, package_count: int, gross_share: float}>
     */
    public static function linkedCouriers(int $agencyId, int $count): array
    {
        $assignments = collect(AgencyCourierDummyData::all())
            ->where('agency_id', $agencyId)
            ->where('status', 'active')
            ->take($count)
            ->values();

        if ($assignments->isEmpty()) {
            return collect(range(1, min($count, 3)))
                ->map(fn (int $i) => [
                    'name' => 'Kurye #'.$i,
                    'package_count' => 0,
                    'gross_share' => 0.0,
                ])
                ->all();
        }

        return $assignments->map(function (array $record, int $index) use ($count) {
            $packages = $count > 0 ? (int) round(1200 + ($index * 340)) : 0;

            return [
                'name' => $record['courier_name'] ?? '—',
                'package_count' => $packages,
                'gross_share' => round($packages * 5.2, 2),
            ];
        })->all();
    }

    public static function find(int $id, bool $withTrashed = false): ?array
    {
        foreach (self::all($withTrashed) as $earning) {
            if ($earning['id'] === $id) {
                return $earning;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        return collect(self::all())
            ->filter(function (array $earning) use ($filters) {
                if (! empty($filters['agency_id']) && $filters['agency_id'] !== 'all') {
                    if ((int) $earning['agency_id'] !== (int) $filters['agency_id']) {
                        return false;
                    }
                }

                if (! empty($filters['period_month']) && $filters['period_month'] !== 'all') {
                    if ((int) $earning['period_month'] !== (int) $filters['period_month']) {
                        return false;
                    }
                }

                if (! empty($filters['period_year']) && $filters['period_year'] !== 'all') {
                    if ((int) $earning['period_year'] !== (int) $filters['period_year']) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($earning['status'] !== $filters['status']) {
                        return false;
                    }
                }

                if (! empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
                    if ($earning['payment_status'] !== $filters['payment_status']) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(fn ($e) => sprintf('%04d-%02d', $e['period_year'], $e['period_month']))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public static function summarize(array $filters = []): array
    {
        $items = self::filter($filters);
        $active = collect($items)->where('payment_status', '!=', 'cancelled');
        $currentMonth = 7;
        $currentYear = 2026;

        return [
            'count' => count($items),
            'total_payable' => round($active->sum('net_payment'), 2),
            'paid_amount' => round($active->whereIn('payment_status', ['paid', 'partial'])->sum('paid_amount'), 2),
            'pending_count' => $active->where('payment_status', 'pending')->count(),
            'this_month_count' => collect($items)
                ->where('period_month', $currentMonth)
                ->where('period_year', $currentYear)
                ->count(),
        ];
    }
}
