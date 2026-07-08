<?php

namespace App\Modules\Courier\Data;

use App\Support\DemoData;
use App\Core\Helpers\MoneyCalculator;
use App\Modules\Business\Data\BusinessAssignmentDummyData;
use App\Modules\Business\Data\BusinessContactDummyData;
use App\Modules\Business\Data\BusinessDummyData;
use Carbon\Carbon;

class CourierEarningDummyData
{
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
    public static function courierTypes(): array
    {
        return [
            'independent' => 'Esnaf Kurye',
            'agency' => 'Acente Kuryesi',
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
     * @return array<int, array<string, mixed>>
     */
    public static function all(bool $withTrashed = false): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

$raw = [
            ['id' => 1, 'courier_id' => 1, 'business_id' => 1, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 1250, 'unit_price' => 38, 'earning_amount' => 47500, 'extra_payment' => 500, 'deduction' => 200, 'payment_status' => 'paid', 'payment_date' => '2026-07-05', 'paid_amount' => 47800, 'description' => 'Haziran paket hakedişi', 'deleted_at' => null],
            ['id' => 2, 'courier_id' => 3, 'business_id' => 1, 'agency_id' => 1, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 980, 'unit_price' => 36, 'earning_amount' => 35280, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-07-05', 'paid_amount' => 35280, 'description' => null, 'deleted_at' => null],
            ['id' => 3, 'courier_id' => 4, 'business_id' => 2, 'agency_id' => 2, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 2100, 'unit_price' => 35, 'earning_amount' => 73500, 'extra_payment' => 1000, 'deduction' => 500, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => 'Napoli yoğun dönem', 'deleted_at' => null],
            ['id' => 4, 'courier_id' => 10, 'business_id' => 3, 'agency_id' => 2, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 0, 'unit_price' => 0, 'earning_amount' => 80000, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => 'Aylık sabit ödeme', 'deleted_at' => null],
            ['id' => 5, 'courier_id' => 2, 'business_id' => 4, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 4500, 'unit_price' => 30, 'earning_amount' => 135000, 'extra_payment' => 2500, 'deduction' => 1200, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => 'E-ticaret Haziran', 'deleted_at' => null],
            ['id' => 6, 'courier_id' => 6, 'business_id' => 4, 'agency_id' => 1, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 3200, 'unit_price' => 29, 'earning_amount' => 92800, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => null, 'deleted_at' => null],
            ['id' => 7, 'courier_id' => 7, 'business_id' => 5, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 0, 'unit_price' => 0, 'earning_amount' => 38000, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'partial', 'payment_date' => '2026-06-28', 'paid_amount' => 20000, 'description' => 'Saatlik model — kısmi ödeme', 'deleted_at' => null],
            ['id' => 8, 'courier_id' => 8, 'business_id' => 6, 'agency_id' => 3, 'period_month' => 5, 'period_year' => 2026, 'package_count' => 650, 'unit_price' => 34, 'earning_amount' => 22100, 'extra_payment' => 0, 'deduction' => 100, 'payment_status' => 'paid', 'payment_date' => '2026-06-10', 'paid_amount' => 22000, 'description' => null, 'deleted_at' => null],
            ['id' => 9, 'courier_id' => 5, 'business_id' => 7, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 0, 'unit_price' => 0, 'earning_amount' => 52000, 'extra_payment' => 1500, 'deduction' => 500, 'payment_status' => 'paid', 'payment_date' => '2026-07-01', 'paid_amount' => 53000, 'description' => 'Kasap aylık sabit', 'deleted_at' => null],
            ['id' => 10, 'courier_id' => 9, 'business_id' => 8, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 0, 'unit_price' => 0, 'earning_amount' => 28000, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => 'Günlük ücret modeli', 'deleted_at' => null],
            ['id' => 11, 'courier_id' => 1, 'business_id' => 1, 'agency_id' => null, 'period_month' => 5, 'period_year' => 2026, 'package_count' => 1180, 'unit_price' => 38, 'earning_amount' => 44840, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-06-08', 'paid_amount' => 44840, 'description' => null, 'deleted_at' => null],
            ['id' => 12, 'courier_id' => 8, 'business_id' => 2, 'agency_id' => 3, 'period_month' => 5, 'period_year' => 2026, 'package_count' => 1850, 'unit_price' => 35, 'earning_amount' => 64750, 'extra_payment' => 800, 'deduction' => 300, 'payment_status' => 'paid', 'payment_date' => '2026-06-12', 'paid_amount' => 65250, 'description' => null, 'deleted_at' => null],
            ['id' => 13, 'courier_id' => 11, 'business_id' => 1, 'agency_id' => null, 'period_month' => 7, 'period_year' => 2026, 'package_count' => 890, 'unit_price' => 38, 'earning_amount' => 33820, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => 'Temmuz devam ediyor', 'deleted_at' => null],
            ['id' => 14, 'courier_id' => 2, 'business_id' => 4, 'agency_id' => null, 'period_month' => 5, 'period_year' => 2026, 'package_count' => 4100, 'unit_price' => 30, 'earning_amount' => 123000, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-06-15', 'paid_amount' => 123000, 'description' => null, 'deleted_at' => null],
            ['id' => 15, 'courier_id' => 6, 'business_id' => 1, 'agency_id' => 1, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 720, 'unit_price' => 37, 'earning_amount' => 26640, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'cancelled', 'payment_date' => null, 'paid_amount' => 0, 'description' => 'İptal — tekrar hesaplanacak', 'deleted_at' => null],
            ['id' => 16, 'courier_id' => 12, 'business_id' => 2, 'agency_id' => 1, 'period_month' => 7, 'period_year' => 2026, 'package_count' => 1420, 'unit_price' => 35, 'earning_amount' => 49700, 'extra_payment' => 500, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => null, 'deleted_at' => null],
            ['id' => 17, 'courier_id' => 16, 'business_id' => 5, 'agency_id' => null, 'period_month' => 7, 'period_year' => 2026, 'package_count' => 0, 'unit_price' => 0, 'earning_amount' => 36000, 'extra_payment' => 0, 'deduction' => 200, 'payment_status' => 'partial', 'payment_date' => '2026-07-06', 'paid_amount' => 18000, 'description' => 'Kısmi ödeme yapıldı', 'deleted_at' => null],
            ['id' => 18, 'courier_id' => 18, 'business_id' => 7, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 310, 'unit_price' => 42, 'earning_amount' => 13020, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-07-03', 'paid_amount' => 13020, 'description' => null, 'deleted_at' => null],
            ['id' => 19, 'courier_id' => 19, 'business_id' => 1, 'agency_id' => 1, 'period_month' => 7, 'period_year' => 2026, 'package_count' => 560, 'unit_price' => 37, 'earning_amount' => 20720, 'extra_payment' => 0, 'deduction' => 150, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => null, 'deleted_at' => null],
            ['id' => 20, 'courier_id' => 24, 'business_id' => 4, 'agency_id' => 2, 'period_month' => 7, 'period_year' => 2026, 'package_count' => 2800, 'unit_price' => 29, 'earning_amount' => 81200, 'extra_payment' => 1200, 'deduction' => 800, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => 'Temmuz HızlıAl', 'deleted_at' => null],
            ['id' => 21, 'courier_id' => 13, 'business_id' => 3, 'agency_id' => null, 'period_month' => 4, 'period_year' => 2026, 'package_count' => 890, 'unit_price' => 32, 'earning_amount' => 28480, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-05-10', 'paid_amount' => 28480, 'description' => null, 'deleted_at' => null],
            ['id' => 22, 'courier_id' => 15, 'business_id' => 4, 'agency_id' => 3, 'period_month' => 5, 'period_year' => 2026, 'package_count' => 2200, 'unit_price' => 29, 'earning_amount' => 63800, 'extra_payment' => 0, 'deduction' => 400, 'payment_status' => 'paid', 'payment_date' => '2026-06-18', 'paid_amount' => 63400, 'description' => null, 'deleted_at' => null],
            ['id' => 23, 'courier_id' => 20, 'business_id' => 3, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 420, 'unit_price' => 33, 'earning_amount' => 13860, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-07-02', 'paid_amount' => 13860, 'description' => 'Yaya kurye', 'deleted_at' => null],
            ['id' => 24, 'courier_id' => 21, 'business_id' => 2, 'agency_id' => null, 'period_month' => 7, 'period_year' => 2026, 'package_count' => 380, 'unit_price' => 34, 'earning_amount' => 12920, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => 'Bisiklet kurye', 'deleted_at' => null],
            ['id' => 25, 'courier_id' => 22, 'business_id' => 8, 'agency_id' => 3, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 1100, 'unit_price' => 31, 'earning_amount' => 34100, 'extra_payment' => 300, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-07-04', 'paid_amount' => 34400, 'description' => null, 'deleted_at' => null],
            ['id' => 26, 'courier_id' => 25, 'business_id' => 5, 'agency_id' => null, 'period_month' => 7, 'period_year' => 2026, 'package_count' => 0, 'unit_price' => 0, 'earning_amount' => 42000, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => 'Aylık sabit Temmuz', 'deleted_at' => null],
            ['id' => 27, 'courier_id' => 27, 'business_id' => 6, 'agency_id' => null, 'period_month' => 5, 'period_year' => 2026, 'package_count' => 780, 'unit_price' => 33, 'earning_amount' => 25740, 'extra_payment' => 0, 'deduction' => 100, 'payment_status' => 'paid', 'payment_date' => '2026-06-05', 'paid_amount' => 25640, 'description' => null, 'deleted_at' => null],
            ['id' => 28, 'courier_id' => 28, 'business_id' => 3, 'agency_id' => null, 'period_month' => 7, 'period_year' => 2026, 'package_count' => 520, 'unit_price' => 32, 'earning_amount' => 16640, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => null, 'deleted_at' => null],
            ['id' => 29, 'courier_id' => 29, 'business_id' => 7, 'agency_id' => 3, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 0, 'unit_price' => 0, 'earning_amount' => 48000, 'extra_payment' => 1000, 'deduction' => 0, 'payment_status' => 'partial', 'payment_date' => '2026-06-30', 'paid_amount' => 30000, 'description' => 'Araç kurye sabit', 'deleted_at' => null],
            ['id' => 30, 'courier_id' => 31, 'business_id' => 2, 'agency_id' => 2, 'period_month' => 7, 'period_year' => 2026, 'package_count' => 1650, 'unit_price' => 35, 'earning_amount' => 57750, 'extra_payment' => 0, 'deduction' => 250, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => null, 'deleted_at' => null],
            ['id' => 31, 'courier_id' => 4, 'business_id' => 2, 'agency_id' => 2, 'period_month' => 4, 'period_year' => 2026, 'package_count' => 1920, 'unit_price' => 35, 'earning_amount' => 67200, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-05-08', 'paid_amount' => 67200, 'description' => null, 'deleted_at' => null],
            ['id' => 32, 'courier_id' => 17, 'business_id' => 6, 'agency_id' => 2, 'period_month' => 6, 'period_year' => 2026, 'package_count' => 0, 'unit_price' => 0, 'earning_amount' => 35000, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'cancelled', 'payment_date' => null, 'paid_amount' => 0, 'description' => 'İzinli dönem — iptal', 'deleted_at' => null],
            ['id' => 33, 'courier_id' => 3, 'business_id' => 5, 'agency_id' => 1, 'period_month' => 4, 'period_year' => 2026, 'package_count' => 0, 'unit_price' => 0, 'earning_amount' => 45000, 'extra_payment' => 0, 'deduction' => 500, 'payment_status' => 'paid', 'payment_date' => '2026-05-12', 'paid_amount' => 44500, 'description' => 'Nisan sabit', 'deleted_at' => null],
            ['id' => 34, 'courier_id' => 26, 'business_id' => 1, 'agency_id' => 1, 'period_month' => 7, 'period_year' => 2026, 'package_count' => 640, 'unit_price' => 37, 'earning_amount' => 23680, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'pending', 'payment_date' => null, 'paid_amount' => 0, 'description' => null, 'deleted_at' => '2026-07-01'],
            ['id' => 35, 'courier_id' => 14, 'business_id' => 4, 'agency_id' => null, 'period_month' => 3, 'period_year' => 2026, 'package_count' => 3600, 'unit_price' => 30, 'earning_amount' => 108000, 'extra_payment' => 0, 'deduction' => 0, 'payment_status' => 'paid', 'payment_date' => '2026-04-10', 'paid_amount' => 108000, 'description' => null, 'deleted_at' => '2026-06-15'],
        ];

        return collect($raw)
            ->filter(fn (array $row) => $withTrashed || $row['deleted_at'] === null)
            ->map(fn (array $row) => self::enrich($row))
            ->sortByDesc(fn ($e) => sprintf('%04d-%02d', $e['period_year'], $e['period_month']))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function enrich(array $row): array
    {
        $courier = collect(CourierDummyData::all())->firstWhere('id', $row['courier_id']);
        $business = collect(BusinessDummyData::all())->firstWhere('id', $row['business_id']);
        $agency = $row['agency_id']
            ? collect(BusinessAssignmentDummyData::agencies())->firstWhere('id', $row['agency_id'])
            : null;

        $earningAmount = (float) $row['earning_amount'];
        $extraPayment = (float) ($row['extra_payment'] ?? 0);
        $deduction = (float) ($row['deduction'] ?? 0);
        $netPayment = round($earningAmount + $extraPayment - $deduction, 2);
        $paidAmount = (float) ($row['paid_amount'] ?? 0);
        $months = self::months();

        $paymentDate = $row['payment_date']
            ? Carbon::parse($row['payment_date'])->format('d.m.Y')
            : '—';

        return array_merge($row, [
            'uuid' => 'cern-'.str_pad((string) $row['id'], 3, '0', STR_PAD_LEFT),
            'courier_name' => $courier['full_name'] ?? '—',
            'courier_type' => $courier['courier_type'] ?? 'independent',
            'courier_type_label' => $courier['courier_type_label'] ?? '—',
            'courier_phone' => $courier['phone'] ?? '—',
            'business_name' => $business['company_name'] ?? '—',
            'business_brand' => $business['brand_name'] ?? '—',
            'agency_name' => $agency['name'] ?? '—',
            'period_label' => ($months[$row['period_month']] ?? '').' '.$row['period_year'],
            'net_payment' => $netPayment,
            'net_payment_formatted' => MoneyCalculator::format($netPayment),
            'earning_amount_formatted' => MoneyCalculator::format((float) $row['earning_amount']),
            'payment_status_label' => self::paymentStatuses()[$row['payment_status']] ?? '—',
            'payment_date_formatted' => $paymentDate,
            'remaining_payment' => max(0, round($netPayment - $paidAmount, 2)),
            'extra_payments' => $extraPayment > 0
                ? [['label' => 'Ek Ödeme', 'amount' => $extraPayment]]
                : [],
            'deductions' => $deduction > 0
                ? [['label' => 'Kesinti', 'amount' => $deduction]]
                : [],
        ]);
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
     * @return array<int, array{id: int, name: string}>
     */
    public static function couriers(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(CourierDummyData::all())
            ->map(fn (array $c) => ['id' => $c['id'], 'name' => $c['full_name']])
            ->values()
            ->all();
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
     * @return array<string, float|int>
     */
    public static function summarize(array $items): array
    {
        $active = collect($items)->where('payment_status', '!=', 'cancelled');
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

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

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        return collect(self::all())
            ->filter(function (array $earning) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $earning['courier_name'],
                        $earning['business_name'],
                    ]));

                    if (! str_contains($haystack, $search)) {
                        return false;
                    }
                }

                if (! empty($filters['courier_id']) && $filters['courier_id'] !== 'all') {
                    if ((int) $earning['courier_id'] !== (int) $filters['courier_id']) {
                        return false;
                    }
                }

                if (! empty($filters['business_id']) && $filters['business_id'] !== 'all') {
                    if ((int) $earning['business_id'] !== (int) $filters['business_id']) {
                        return false;
                    }
                }

                if (! empty($filters['agency_id']) && $filters['agency_id'] !== 'all') {
                    if ((int) ($earning['agency_id'] ?? 0) !== (int) $filters['agency_id']) {
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

                if (! empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
                    if ($earning['payment_status'] !== $filters['payment_status']) {
                        return false;
                    }
                }

                if (! empty($filters['courier_type']) && $filters['courier_type'] !== 'all') {
                    if ($earning['courier_type'] !== $filters['courier_type']) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(fn ($e) => sprintf('%04d-%02d', $e['period_year'], $e['period_month']))
            ->values()
            ->all();
    }
}
