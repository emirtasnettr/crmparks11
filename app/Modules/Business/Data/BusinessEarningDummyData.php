<?php

namespace App\Modules\Business\Data;

use App\Core\Helpers\MoneyCalculator;

class BusinessEarningDummyData
{
    /**
     * @return array<string, string>
     */
    public static function statuses(): array
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
    public static function pricingModels(): array
    {
        return [
            'per_package' => 'Paket Başı',
            'monthly_fixed' => 'Aylık Sabit',
            'hourly' => 'Saatlik',
            'daily' => 'Günlük',
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
    public static function all(): array
    {
        $raw = [
            ['id' => 1, 'business_id' => 1, 'courier_id' => 1, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 1250, 'revenue_unit_price' => 45, 'courier_unit_price' => 38, 'extra_income' => 500, 'extra_expense' => 0, 'deduction' => 200, 'status' => 'paid', 'description' => 'Haziran paket hakedişi'],
            ['id' => 2, 'business_id' => 1, 'courier_id' => 3, 'agency_id' => 1, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 980, 'revenue_unit_price' => 45, 'courier_unit_price' => 36, 'extra_income' => 0, 'extra_expense' => 150, 'deduction' => 0, 'status' => 'paid', 'description' => null],
            ['id' => 3, 'business_id' => 2, 'courier_id' => 4, 'agency_id' => 2, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 2100, 'revenue_unit_price' => 42, 'courier_unit_price' => 35, 'extra_income' => 1000, 'extra_expense' => 300, 'deduction' => 500, 'status' => 'approved', 'description' => 'Napoli yoğun dönem'],
            ['id' => 4, 'business_id' => 3, 'courier_id' => 10, 'agency_id' => 2, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'monthly_fixed', 'package_count' => 0, 'revenue_unit_price' => 0, 'courier_unit_price' => 0, 'revenue_total' => 100000, 'courier_payment' => 80000, 'extra_income' => 0, 'extra_expense' => 2000, 'deduction' => 0, 'status' => 'approved', 'description' => 'Aylık sabit sözleşme'],
            ['id' => 5, 'business_id' => 4, 'courier_id' => 2, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 4500, 'revenue_unit_price' => 35, 'courier_unit_price' => 30, 'extra_income' => 2500, 'extra_expense' => 800, 'deduction' => 1200, 'status' => 'pending', 'description' => 'E-ticaret Haziran'],
            ['id' => 6, 'business_id' => 4, 'courier_id' => 6, 'agency_id' => 1, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 3200, 'revenue_unit_price' => 35, 'courier_unit_price' => 29, 'extra_income' => 0, 'extra_expense' => 0, 'deduction' => 0, 'status' => 'pending', 'description' => null],
            ['id' => 7, 'business_id' => 5, 'courier_id' => 7, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'hourly', 'package_count' => 0, 'revenue_unit_price' => 0, 'courier_unit_price' => 0, 'revenue_total' => 48000, 'courier_payment' => 38000, 'extra_income' => 0, 'extra_expense' => 500, 'deduction' => 0, 'status' => 'draft', 'description' => 'Saatlik çalışma modeli'],
            ['id' => 8, 'business_id' => 6, 'courier_id' => 8, 'agency_id' => 3, 'period_month' => 5, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 650, 'revenue_unit_price' => 40, 'courier_unit_price' => 34, 'extra_income' => 0, 'extra_expense' => 0, 'deduction' => 100, 'status' => 'paid', 'description' => null],
            ['id' => 9, 'business_id' => 7, 'courier_id' => 5, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'monthly_fixed', 'package_count' => 0, 'revenue_unit_price' => 0, 'courier_unit_price' => 0, 'revenue_total' => 65000, 'courier_payment' => 52000, 'extra_income' => 1500, 'extra_expense' => 0, 'deduction' => 500, 'status' => 'paid', 'description' => 'Kasap aylık sabit'],
            ['id' => 10, 'business_id' => 8, 'courier_id' => 9, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'daily', 'package_count' => 0, 'revenue_unit_price' => 0, 'courier_unit_price' => 0, 'revenue_total' => 36000, 'courier_payment' => 28000, 'extra_income' => 0, 'extra_expense' => 400, 'deduction' => 0, 'status' => 'approved', 'description' => 'Günlük ücret modeli'],
            ['id' => 11, 'business_id' => 1, 'courier_id' => 1, 'agency_id' => null, 'period_month' => 5, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 1180, 'revenue_unit_price' => 45, 'courier_unit_price' => 38, 'extra_income' => 0, 'extra_expense' => 0, 'deduction' => 0, 'status' => 'paid', 'description' => null],
            ['id' => 12, 'business_id' => 2, 'courier_id' => 8, 'agency_id' => 3, 'period_month' => 5, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 1850, 'revenue_unit_price' => 42, 'courier_unit_price' => 35, 'extra_income' => 800, 'extra_expense' => 0, 'deduction' => 300, 'status' => 'paid', 'description' => null],
            ['id' => 13, 'business_id' => 3, 'courier_id' => 10, 'agency_id' => 2, 'period_month' => 5, 'period_year' => 2026, 'pricing_model' => 'monthly_fixed', 'package_count' => 0, 'revenue_unit_price' => 0, 'courier_unit_price' => 0, 'revenue_total' => 100000, 'courier_payment' => 80000, 'extra_income' => 0, 'extra_expense' => 1500, 'deduction' => 0, 'status' => 'paid', 'description' => null],
            ['id' => 14, 'business_id' => 4, 'courier_id' => 2, 'agency_id' => null, 'period_month' => 5, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 4100, 'revenue_unit_price' => 35, 'courier_unit_price' => 30, 'extra_income' => 0, 'extra_expense' => 600, 'deduction' => 0, 'status' => 'paid', 'description' => null],
            ['id' => 15, 'business_id' => 1, 'courier_id' => 6, 'agency_id' => 1, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 720, 'revenue_unit_price' => 45, 'courier_unit_price' => 37, 'extra_income' => 0, 'extra_expense' => 0, 'deduction' => 0, 'status' => 'cancelled', 'description' => 'İptal edildi — tekrar hesaplanacak'],
            ['id' => 16, 'business_id' => 5, 'courier_id' => 7, 'agency_id' => null, 'period_month' => 5, 'period_year' => 2026, 'pricing_model' => 'hourly', 'package_count' => 0, 'revenue_unit_price' => 0, 'courier_unit_price' => 0, 'revenue_total' => 45000, 'courier_payment' => 36000, 'extra_income' => 0, 'extra_expense' => 0, 'deduction' => 0, 'status' => 'paid', 'description' => null],
            ['id' => 17, 'business_id' => 6, 'courier_id' => 8, 'agency_id' => 3, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 580, 'revenue_unit_price' => 40, 'courier_unit_price' => 33, 'extra_income' => 200, 'extra_expense' => 0, 'deduction' => 0, 'status' => 'pending', 'description' => null],
            ['id' => 18, 'business_id' => 7, 'courier_id' => 5, 'agency_id' => null, 'period_month' => 5, 'period_year' => 2026, 'pricing_model' => 'monthly_fixed', 'package_count' => 0, 'revenue_unit_price' => 0, 'courier_unit_price' => 0, 'revenue_total' => 65000, 'courier_payment' => 52000, 'extra_income' => 0, 'extra_expense' => 1000, 'deduction' => 0, 'status' => 'paid', 'description' => null],
            ['id' => 19, 'business_id' => 8, 'courier_id' => 9, 'agency_id' => null, 'period_month' => 5, 'period_year' => 2026, 'pricing_model' => 'daily', 'package_count' => 0, 'revenue_unit_price' => 0, 'courier_unit_price' => 0, 'revenue_total' => 33000, 'courier_payment' => 26000, 'extra_income' => 500, 'extra_expense' => 0, 'deduction' => 200, 'status' => 'paid', 'description' => null],
            ['id' => 20, 'business_id' => 2, 'courier_id' => 4, 'agency_id' => 2, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 1950, 'revenue_unit_price' => 42, 'courier_unit_price' => 35, 'extra_income' => 0, 'extra_expense' => 250, 'deduction' => 0, 'status' => 'draft', 'description' => 'Taslak kayıt'],
            ['id' => 21, 'business_id' => 3, 'courier_id' => 2, 'agency_id' => null, 'period_month' => 4, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 890, 'revenue_unit_price' => 38, 'courier_unit_price' => 32, 'extra_income' => 0, 'extra_expense' => 0, 'deduction' => 0, 'status' => 'paid', 'description' => null],
            ['id' => 22, 'business_id' => 4, 'courier_id' => 6, 'agency_id' => 1, 'period_month' => 5, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 2800, 'revenue_unit_price' => 35, 'courier_unit_price' => 29, 'extra_income' => 1500, 'extra_expense' => 0, 'deduction' => 800, 'status' => 'approved', 'description' => null],
            ['id' => 23, 'business_id' => 1, 'courier_id' => 2, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 540, 'revenue_unit_price' => 45, 'courier_unit_price' => 38, 'extra_income' => 0, 'extra_expense' => 0, 'deduction' => 0, 'status' => 'pending', 'description' => 'Destek kurye'],
            ['id' => 24, 'business_id' => 5, 'courier_id' => 3, 'agency_id' => 1, 'period_month' => 4, 'period_year' => 2026, 'pricing_model' => 'monthly_fixed', 'package_count' => 0, 'revenue_unit_price' => 0, 'courier_unit_price' => 0, 'revenue_total' => 55000, 'courier_payment' => 45000, 'extra_income' => 0, 'extra_expense' => 500, 'deduction' => 0, 'status' => 'paid', 'description' => 'Nisan sabit'],
            ['id' => 25, 'business_id' => 6, 'courier_id' => 8, 'agency_id' => 3, 'period_month' => 4, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 420, 'revenue_unit_price' => 40, 'courier_unit_price' => 34, 'extra_income' => 0, 'extra_expense' => 0, 'deduction' => 50, 'status' => 'paid', 'description' => null],
            ['id' => 26, 'business_id' => 7, 'courier_id' => 5, 'agency_id' => null, 'period_month' => 6, 'period_year' => 2026, 'pricing_model' => 'per_package', 'package_count' => 310, 'revenue_unit_price' => 50, 'courier_unit_price' => 55, 'extra_income' => 0, 'extra_expense' => 200, 'deduction' => 0, 'status' => 'approved', 'description' => 'Zararlı dönem örneği'],
        ];

        return array_map(fn (array $row) => self::enrich($row), $raw);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function enrich(array $row): array
    {
        $business = collect(BusinessDummyData::all())->firstWhere('id', $row['business_id']);
        $courier = collect(BusinessAssignmentDummyData::couriers())->firstWhere('id', $row['courier_id']);
        $agency = $row['agency_id']
            ? collect(BusinessAssignmentDummyData::agencies())->firstWhere('id', $row['agency_id'])
            : null;

        if ($row['pricing_model'] === 'per_package') {
            $revenue = round((int) $row['package_count'] * (float) $row['revenue_unit_price'], 2);
            $courierPayment = round((int) $row['package_count'] * (float) $row['courier_unit_price'], 2);
        } else {
            $revenue = (float) ($row['revenue_total'] ?? 0);
            $courierPayment = (float) ($row['courier_payment'] ?? 0);
        }

        $extraIncome = (float) ($row['extra_income'] ?? 0);
        $extraExpense = (float) ($row['extra_expense'] ?? 0);
        $deduction = (float) ($row['deduction'] ?? 0);

        $profit = round($revenue - $courierPayment - $extraExpense + $extraIncome - $deduction, 2);
        $totalExpense = round($courierPayment + $extraExpense, 2);

        $months = self::months();

        return array_merge($row, [
            'business_name' => $business['company_name'] ?? '—',
            'courier_name' => $courier['name'] ?? '—',
            'agency_name' => $agency['name'] ?? '—',
            'pricing_model_label' => self::pricingModels()[$row['pricing_model']] ?? '—',
            'period_label' => ($months[$row['period_month']] ?? '').' '.$row['period_year'],
            'revenue' => $revenue,
            'courier_payment' => $courierPayment,
            'total_expense' => $totalExpense,
            'profit' => $profit,
            'status_label' => self::statuses()[$row['status']] ?? '—',
            'revenue_formatted' => MoneyCalculator::format($revenue),
            'courier_payment_formatted' => MoneyCalculator::format($courierPayment),
            'total_expense_formatted' => MoneyCalculator::format($totalExpense),
            'profit_formatted' => MoneyCalculator::format($profit),
        ]);
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $earning) {
            if ($earning['id'] === $id) {
                return $earning;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, float|int>
     */
    public static function summarize(array $items): array
    {
        return [
            'count' => count($items),
            'total_revenue' => round(collect($items)->sum('revenue'), 2),
            'total_expense' => round(collect($items)->sum('total_expense'), 2),
            'total_profit' => round(collect($items)->sum('profit'), 2),
            'pending_count' => collect($items)->where('status', 'pending')->count(),
            'paid_count' => collect($items)->where('status', 'paid')->count(),
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
                if (! empty($filters['business_id']) && $filters['business_id'] !== 'all') {
                    if ((int) $earning['business_id'] !== (int) $filters['business_id']) {
                        return false;
                    }
                }

                if (! empty($filters['courier_id']) && $filters['courier_id'] !== 'all') {
                    if ((int) $earning['courier_id'] !== (int) $filters['courier_id']) {
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

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($earning['status'] !== $filters['status']) {
                        return false;
                    }
                }

                if (! empty($filters['pricing_model']) && $filters['pricing_model'] !== 'all') {
                    if ($earning['pricing_model'] !== $filters['pricing_model']) {
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
