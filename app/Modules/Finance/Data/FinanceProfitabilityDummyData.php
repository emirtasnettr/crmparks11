<?php

namespace App\Modules\Finance\Data;

use App\Support\DemoData;
use App\Core\Helpers\MoneyCalculator;

use App\Modules\Agency\Data\AgencyDummyData;
use App\Modules\Business\Data\BusinessDummyData;
use App\Modules\Courier\Data\CourierDummyData;
use Carbon\Carbon;

class FinanceProfitabilityDummyData
{
    private const REFERENCE_DATE = '2026-07-07';

    /**
     * @return array<string, string>
     */
    public static function dateRanges(): array
    {
        return [
            'all' => 'Tümü',
            'month' => 'Bu Ay',
            'quarter' => 'Bu Çeyrek',
            'year' => 'Bu Yıl',
            'last_6_months' => 'Son 6 Ay',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function pricingModels(): array
    {
        return [
            'per_package' => 'Paket Başı',
            'fixed' => 'Aylık Sabit',
            'hourly' => 'Saatlik',
            'daily' => 'Günlük',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function profitMarginFilters(): array
    {
        return [
            'all' => 'Tümü',
            'high' => 'Yüksek (≥ %25)',
            'medium' => 'Orta (%10 - %24)',
            'low' => 'Düşük (%0 - %9)',
            'negative' => 'Negatif',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function cities(): array
    {
        return BusinessDummyData::cities();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function businesses(): array
    {
        return collect(BusinessDummyData::all())
            ->map(fn (array $b) => ['id' => $b['id'], 'name' => $b['company_name']])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function couriers(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(CourierDummyData::raw())
            ->map(fn (array $c) => [
                'id' => $c['id'],
                'name' => trim($c['first_name'].' '.$c['last_name']),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function agencies(): array
    {
        return collect(AgencyDummyData::all())
            ->map(fn (array $a) => ['id' => $a['id'], 'name' => $a['company_name']])
            ->all();
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public static function analyze(array $filters): array
    {
        $businessRows = self::filterBusinessRows(self::businessProfitabilityRows(), $filters);
        $agencyRows = self::filterAgencyRows(self::agencyProfitabilityRows(), $filters);
        $courierRows = self::filterCourierRows(self::courierCostRows(), $filters);
        $operationRows = self::filterOperationRows(self::operationProfitabilityRows(), $filters);

        $scale = self::scaleForDateRange($filters['date_range'] ?? 'month');

        $businessRows = self::scaleRows($businessRows, $scale);
        $agencyRows = self::scaleRows($agencyRows, $scale);
        $courierRows = self::scaleRows($courierRows, $scale);
        $operationRows = self::scaleRows($operationRows, $scale);

        $kpis = self::buildKpis($businessRows, $operationRows, $agencyRows);
        $charts = self::buildCharts($businessRows, $agencyRows, $scale);

        $sortedBusiness = collect($businessRows)->sortByDesc('net_profit')->values();
        $sortedAgency = collect($agencyRows)->sortByDesc('net_profit')->values();
        $sortedOperations = collect($operationRows)->sortByDesc('net_profit')->values();

        return [
            'kpis' => $kpis,
            'charts' => $charts,
            'business_table' => $sortedBusiness->all(),
            'agency_table' => $sortedAgency->all(),
            'courier_table' => collect($courierRows)->sortByDesc('total_cost')->values()->all(),
            'top_businesses' => $sortedBusiness->take(10)->all(),
            'top_agencies' => $sortedAgency->take(10)->all(),
            'top_operations' => $sortedOperations->take(10)->all(),
            'bottom_businesses' => $sortedBusiness->sortBy('net_profit')->take(10)->values()->all(),
            'bottom_agencies' => $sortedAgency->sortBy('net_profit')->take(10)->values()->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function businessProfitabilityRows(): array
    {
        $templates = [
            ['id' => 1, 'packages' => 12450, 'revenue' => 1845000, 'courier' => 1128000, 'agency' => 285000, 'other' => 62000],
            ['id' => 2, 'packages' => 9850, 'revenue' => 1285000, 'courier' => 798000, 'agency' => 198000, 'other' => 45000],
            ['id' => 3, 'packages' => 4200, 'revenue' => 845000, 'courier' => 512000, 'agency' => 142000, 'other' => 38000],
            ['id' => 4, 'packages' => 28600, 'revenue' => 2450000, 'courier' => 1685000, 'agency' => 412000, 'other' => 98000],
            ['id' => 5, 'packages' => 3100, 'revenue' => 468000, 'courier' => 298000, 'agency' => 72000, 'other' => 22000],
            ['id' => 6, 'packages' => 1850, 'revenue' => 312000, 'courier' => 228000, 'agency' => 54000, 'other' => 18000],
            ['id' => 7, 'packages' => 920, 'revenue' => 198000, 'courier' => 142000, 'agency' => 28000, 'other' => 12000],
            ['id' => 8, 'packages' => 640, 'revenue' => 124000, 'courier' => 98000, 'agency' => 18000, 'other' => 8000],
        ];

        return collect($templates)->map(function (array $tpl) {
            $business = collect(BusinessDummyData::all())->firstWhere('id', $tpl['id']);
            $netProfit = round($tpl['revenue'] - $tpl['courier'] - $tpl['agency'] - $tpl['other'], 2);
            $margin = $tpl['revenue'] > 0 ? round(($netProfit / $tpl['revenue']) * 100, 1) : 0;
            $perPackage = $tpl['packages'] > 0 ? round($netProfit / $tpl['packages'], 2) : 0;

            return [
                'business_id' => $tpl['id'],
                'business_name' => $business['company_name'],
                'city' => $business['city'],
                'pricing_model' => $business['pricing_model'],
                'pricing_model_label' => self::pricingModels()[$business['pricing_model']] ?? $business['pricing_model'],
                'package_count' => $tpl['packages'],
                'revenue' => $tpl['revenue'],
                'courier_cost' => $tpl['courier'],
                'agency_cost' => $tpl['agency'],
                'other_expenses' => $tpl['other'],
                'net_profit' => $netProfit,
                'profit_margin' => $margin,
                'profit_per_package' => $perPackage,
                'revenue_formatted' => self::formatMoney($tpl['revenue']),
                'courier_cost_formatted' => self::formatMoney($tpl['courier']),
                'agency_cost_formatted' => self::formatMoney($tpl['agency']),
                'other_expenses_formatted' => self::formatMoney($tpl['other']),
                'net_profit_formatted' => self::formatMoney($netProfit),
                'profit_margin_formatted' => number_format($margin, 1, ',', '.').'%',
                'profit_per_package_formatted' => self::formatMoney($perPackage),
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function agencyProfitabilityRows(): array
    {
        return collect(AgencyDummyData::all())
            ->filter(fn (array $a) => $a['monthly_earning'] > 0)
            ->take(12)
            ->map(function (array $agency) {
                $packages = $agency['active_couriers'] * (850 + ($agency['id'] * 73) % 420);
                $earning = round((float) $agency['monthly_earning'] * 6.2, 2);
                $cost = round($earning * (0.62 + ($agency['id'] % 5) * 0.04), 2);
                $netProfit = round($earning - $cost, 2);

                return [
                    'agency_id' => $agency['id'],
                    'agency_name' => $agency['company_name'],
                    'city' => $agency['city'],
                    'courier_count' => $agency['active_couriers'],
                    'total_packages' => $packages,
                    'total_earning' => $earning,
                    'total_cost' => $cost,
                    'net_profit' => $netProfit,
                    'total_earning_formatted' => self::formatMoney($earning),
                    'total_cost_formatted' => self::formatMoney($cost),
                    'net_profit_formatted' => self::formatMoney($netProfit),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function courierCostRows(): array
    {
        $rows = [];
        $businessMap = collect(BusinessDummyData::all())->keyBy('company_name');

        foreach (CourierDummyData::raw() as $index => $courier) {
            $business = $businessMap->get($courier['active_business_name']);
            if (! $business) {
                continue;
            }

            $packages = 420 + (($courier['id'] * 137) % 1800);
            $earning = round($packages * (32 + ($courier['id'] % 6)), 2);
            $extra = round(($courier['id'] % 4) * 1250, 2);
            $deduction = round(($courier['id'] % 5) * 480, 2);
            $total = round($earning + $extra - $deduction, 2);

            $rows[] = [
                'courier_id' => $courier['id'],
                'courier_name' => trim($courier['first_name'].' '.$courier['last_name']),
                'agency_id' => $courier['agency_id'],
                'business_id' => $business['id'],
                'business_name' => $business['company_name'],
                'city' => $business['city'],
                'pricing_model' => $business['pricing_model'],
                'package_count' => $packages,
                'earning' => $earning,
                'extra_payment' => $extra,
                'deduction' => $deduction,
                'total_cost' => $total,
                'earning_formatted' => self::formatMoney($earning),
                'extra_payment_formatted' => self::formatMoney($extra),
                'deduction_formatted' => self::formatMoney($deduction),
                'total_cost_formatted' => self::formatMoney($total),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function operationProfitabilityRows(): array
    {
        return collect(self::courierCostRows())->map(function (array $row) {
            $revenue = round($row['package_count'] * (38 + ($row['courier_id'] % 8)), 2);
            $netProfit = round($revenue - $row['total_cost'], 2);

            return array_merge($row, [
                'operation_label' => $row['courier_name'].' — '.$row['business_name'],
                'revenue' => $revenue,
                'net_profit' => $netProfit,
                'revenue_formatted' => self::formatMoney($revenue),
                'net_profit_formatted' => self::formatMoney($netProfit),
            ]);
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    private static function filterBusinessRows(array $rows, array $filters): array
    {
        return collect($rows)
            ->filter(function (array $row) use ($filters) {
                if (($filters['business_id'] ?? 'all') !== 'all' && (int) $row['business_id'] !== (int) $filters['business_id']) {
                    return false;
                }

                if (($filters['city'] ?? 'all') !== 'all' && $row['city'] !== $filters['city']) {
                    return false;
                }

                if (($filters['pricing_model'] ?? 'all') !== 'all' && $row['pricing_model'] !== $filters['pricing_model']) {
                    return false;
                }

                if (! self::matchesMarginFilter($row['profit_margin'], $filters['profit_margin'] ?? 'all')) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    private static function filterAgencyRows(array $rows, array $filters): array
    {
        return collect($rows)
            ->filter(function (array $row) use ($filters) {
                if (($filters['agency_id'] ?? 'all') !== 'all' && (int) $row['agency_id'] !== (int) $filters['agency_id']) {
                    return false;
                }

                if (($filters['city'] ?? 'all') !== 'all' && $row['city'] !== $filters['city']) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    private static function filterCourierRows(array $rows, array $filters): array
    {
        return collect($rows)
            ->filter(function (array $row) use ($filters) {
                if (($filters['courier_id'] ?? 'all') !== 'all' && (int) $row['courier_id'] !== (int) $filters['courier_id']) {
                    return false;
                }

                if (($filters['business_id'] ?? 'all') !== 'all' && (int) $row['business_id'] !== (int) $filters['business_id']) {
                    return false;
                }

                if (($filters['agency_id'] ?? 'all') !== 'all' && (int) ($row['agency_id'] ?? 0) !== (int) $filters['agency_id']) {
                    return false;
                }

                if (($filters['city'] ?? 'all') !== 'all' && $row['city'] !== $filters['city']) {
                    return false;
                }

                if (($filters['pricing_model'] ?? 'all') !== 'all' && $row['pricing_model'] !== $filters['pricing_model']) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    private static function filterOperationRows(array $rows, array $filters): array
    {
        return self::filterCourierRows($rows, $filters);
    }

    private static function matchesMarginFilter(float $margin, string $filter): bool
    {
        return match ($filter) {
            'high' => $margin >= 25,
            'medium' => $margin >= 10 && $margin < 25,
            'low' => $margin >= 0 && $margin < 10,
            'negative' => $margin < 0,
            default => true,
        };
    }

    private static function scaleForDateRange(string $range): float
    {
        return match ($range) {
            'month' => 1.0,
            'quarter' => 2.85,
            'year' => 11.5,
            'last_6_months' => 5.8,
            'all' => 14.0,
            default => 1.0,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private static function scaleRows(array $rows, float $scale): array
    {
        if ($scale === 1.0) {
            return $rows;
        }

        return collect($rows)->map(function (array $row) use ($scale) {
            foreach (['revenue', 'courier_cost', 'agency_cost', 'other_expenses', 'net_profit', 'total_earning', 'total_cost', 'earning', 'extra_payment', 'deduction', 'total_cost'] as $key) {
                if (isset($row[$key]) && is_numeric($row[$key])) {
                    $row[$key] = round($row[$key] * $scale, 2);
                }
            }

            foreach (['package_count', 'total_packages'] as $key) {
                if (isset($row[$key])) {
                    $row[$key] = (int) round($row[$key] * min($scale, 3));
                }
            }

            if (isset($row['revenue'], $row['net_profit']) && $row['revenue'] > 0) {
                $row['profit_margin'] = round(($row['net_profit'] / $row['revenue']) * 100, 1);
                $row['profit_margin_formatted'] = number_format($row['profit_margin'], 1, ',', '.').'%';
            }

            if (isset($row['package_count'], $row['net_profit']) && $row['package_count'] > 0) {
                $row['profit_per_package'] = round($row['net_profit'] / $row['package_count'], 2);
                $row['profit_per_package_formatted'] = self::formatMoney($row['profit_per_package']);
            }

            foreach (['revenue', 'courier_cost', 'agency_cost', 'other_expenses', 'net_profit', 'total_earning', 'total_cost', 'earning', 'extra_payment', 'deduction'] as $key) {
                if (isset($row[$key])) {
                    $row[$key.'_formatted'] = self::formatMoney((float) $row[$key]);
                }
            }

            return $row;
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $businessRows
     * @param  array<int, array<string, mixed>>  $operationRows
     * @param  array<int, array<string, mixed>>  $agencyRows
     * @return array<string, mixed>
     */
    private static function buildKpis(array $businessRows, array $operationRows, array $agencyRows): array
    {
        $totalRevenue = round(collect($businessRows)->sum('revenue'), 2);
        $courierCost = round(collect($businessRows)->sum('courier_cost'), 2);
        $agencyCost = round(collect($businessRows)->sum('agency_cost'), 2);
        $otherExpenses = round(collect($businessRows)->sum('other_expenses'), 2);
        $totalExpense = round($courierCost + $agencyCost + $otherExpenses, 2);
        $netProfit = round($totalRevenue - $totalExpense, 2);
        $margin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0;
        $totalPackages = (int) collect($businessRows)->sum('package_count');
        $perPackage = $totalPackages > 0 ? round($netProfit / $totalPackages, 2) : 0;

        $topBusiness = collect($businessRows)->sortByDesc('net_profit')->first();
        $topAgency = collect($agencyRows)->sortByDesc('net_profit')->first();
        $topOperation = collect($operationRows)->sortByDesc('net_profit')->first();

        return [
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => self::formatMoney($totalRevenue),
            'total_expense' => $totalExpense,
            'total_expense_formatted' => self::formatMoney($totalExpense),
            'net_profit' => $netProfit,
            'net_profit_formatted' => self::formatMoney($netProfit),
            'profit_margin' => $margin,
            'profit_margin_formatted' => number_format($margin, 1, ',', '.').'%',
            'profit_per_package' => $perPackage,
            'profit_per_package_formatted' => self::formatMoney($perPackage),
            'top_business_name' => $topBusiness['business_name'] ?? '—',
            'top_business_profit_formatted' => $topBusiness['net_profit_formatted'] ?? '—',
            'top_agency_name' => $topAgency['agency_name'] ?? '—',
            'top_agency_profit_formatted' => $topAgency['net_profit_formatted'] ?? '—',
            'top_operation_name' => $topOperation['operation_label'] ?? '—',
            'top_operation_profit_formatted' => $topOperation['net_profit_formatted'] ?? '—',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $businessRows
     * @param  array<int, array<string, mixed>>  $agencyRows
     * @return array<string, mixed>
     */
    private static function buildCharts(array $businessRows, array $agencyRows, float $scale): array
    {
        $months = ['Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem'];
        $baseRevenue = [620000, 685000, 710000, 745000, 812000, 845000];
        $baseExpense = [480000, 510000, 528000, 542000, 589000, 612000];

        $revenue = array_map(fn ($v) => (int) round($v * $scale), $baseRevenue);
        $expense = array_map(fn ($v) => (int) round($v * $scale), $baseExpense);
        $profit = array_map(fn ($r, $e) => $r - $e, $revenue, $expense);

        $businessChart = collect($businessRows)
            ->sortByDesc('net_profit')
            ->take(8)
            ->map(fn ($r) => ['label' => $r['business_name'], 'value' => (int) round($r['net_profit'])])
            ->values()
            ->all();

        $agencyChart = collect($agencyRows)
            ->sortByDesc('net_profit')
            ->take(8)
            ->map(fn ($r) => ['label' => $r['agency_name'], 'value' => (int) round($r['net_profit'])])
            ->values()
            ->all();

        $cityChart = collect($businessRows)
            ->groupBy('city')
            ->map(fn ($group, $city) => [
                'label' => $city,
                'value' => (int) round($group->sum('net_profit')),
            ])
            ->sortByDesc('value')
            ->values()
            ->all();

        $revenueDistribution = [
            ['label' => 'Paket Başı', 'value' => (int) round(collect($businessRows)->where('pricing_model', 'per_package')->sum('revenue'))],
            ['label' => 'Aylık Sabit', 'value' => (int) round(collect($businessRows)->where('pricing_model', 'fixed')->sum('revenue'))],
            ['label' => 'Saatlik', 'value' => (int) round(collect($businessRows)->where('pricing_model', 'hourly')->sum('revenue'))],
            ['label' => 'Günlük', 'value' => (int) round(collect($businessRows)->where('pricing_model', 'daily')->sum('revenue'))],
        ];

        return [
            'months' => $months,
            'trend' => [
                'revenue' => $revenue,
                'expense' => $expense,
                'profit' => $profit,
            ],
            'business_profitability' => $businessChart,
            'agency_profitability' => $agencyChart,
            'city_profitability' => $cityChart,
            'revenue_distribution' => array_values(array_filter($revenueDistribution, fn ($i) => $i['value'] > 0)),
        ];
    }

    private static function formatMoney(float $amount): string
    {
        return MoneyCalculator::format($amount);
    }
}
