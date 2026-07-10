<?php

namespace App\Modules\Finance\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Models\EarningLine;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Data\ProfitabilityFormData;
use App\Modules\Finance\Models\FinanceExpense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProfitabilityService
{
    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function analyze(array $filters): array
    {
        $lines = $this->earningLines($filters);

        $businessRows = $this->buildBusinessRows($lines, $filters);
        $agencyRows = $this->buildAgencyRows($lines, $filters);
        $courierRows = $this->buildCourierRows($lines, $filters);
        $operationRows = $this->buildOperationRows($lines, $filters);

        $kpis = $this->buildKpis($businessRows, $operationRows, $agencyRows);
        $charts = $this->buildCharts($businessRows, $agencyRows, $filters);

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
     * @return array<int, array{id: int, name: string}>
     */
    public function businesses(): array
    {
        return Business::query()
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->displayName(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function couriers(): array
    {
        return Courier::query()
            ->orderBy('full_name')
            ->get(['id', 'full_name'])
            ->map(fn (Courier $courier) => [
                'id' => $courier->id,
                'name' => $courier->full_name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function agencies(): array
    {
        return Agency::query()
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Agency $agency) => [
                'id' => $agency->id,
                'name' => $agency->displayName(),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function cities(): array
    {
        return Business::query()
            ->with('city:id,name')
            ->get()
            ->pluck('city.name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $filters
     * @return Collection<int, EarningLine>
     */
    private function earningLines(array $filters): Collection
    {
        $query = EarningLine::query()
            ->with(['business.city', 'courier.agency.city']);

        $this->applyDateRange($query, $filters['date_range'] ?? 'month');

        if (($filters['business_id'] ?? 'all') !== 'all') {
            $query->where('business_id', (int) $filters['business_id']);
        }

        if (($filters['courier_id'] ?? 'all') !== 'all') {
            $query->where('courier_id', (int) $filters['courier_id']);
        }

        if (($filters['agency_id'] ?? 'all') !== 'all') {
            $query->whereHas('courier', fn (Builder $courierQuery) => $courierQuery->where('agency_id', (int) $filters['agency_id']));
        }

        if (($filters['city'] ?? 'all') !== 'all') {
            $query->whereHas('business.city', fn (Builder $cityQuery) => $cityQuery->where('name', $filters['city']));
        }

        if (($filters['pricing_model'] ?? 'all') !== 'all') {
            $pricingModel = $filters['pricing_model'];
            $query->where(function (Builder $pricingQuery) use ($pricingModel): void {
                $pricingQuery->where('pricing_model', $pricingModel);

                if ($pricingModel === 'fixed') {
                    $pricingQuery->orWhere('pricing_model', 'monthly_fixed');
                }
            });
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, EarningLine>  $lines
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function buildBusinessRows(Collection $lines, array $filters): array
    {
        return $lines
            ->groupBy('business_id')
            ->map(function (Collection $businessLines) use ($filters): ?array {
                /** @var EarningLine $first */
                $first = $businessLines->first();
                $business = $first->business;

                if ($business === null) {
                    return null;
                }

                $revenue = round((float) $businessLines->sum('revenue_total'), 2);
                $courierCost = round((float) $businessLines->sum('courier_total'), 2);
                $agencyCost = round((float) $businessLines->sum('agency_payment'), 2);
                $otherExpenses = round((float) $businessLines->sum('extra_expense'), 2);
                $packageCount = (int) $businessLines->sum('package_count');
                $netProfit = round($revenue - $courierCost - $agencyCost - $otherExpenses, 2);
                $margin = $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : 0.0;
                $perPackage = $packageCount > 0 ? round($netProfit / $packageCount, 2) : 0.0;
                $pricingModel = ProfitabilityFormData::normalizePricingModel(
                    $businessLines->pluck('pricing_model')->filter()->countBy()->sortDesc()->keys()->first()
                );

                $row = [
                    'business_id' => $business->id,
                    'business_name' => $business->company_name,
                    'city' => $business->city?->name ?? '—',
                    'pricing_model' => $pricingModel,
                    'pricing_model_label' => ProfitabilityFormData::pricingModels()[$pricingModel] ?? $pricingModel,
                    'package_count' => $packageCount,
                    'revenue' => $revenue,
                    'courier_cost' => $courierCost,
                    'agency_cost' => $agencyCost,
                    'other_expenses' => $otherExpenses,
                    'net_profit' => $netProfit,
                    'profit_margin' => $margin,
                    'profit_per_package' => $perPackage,
                ];

                if (! ProfitabilityFormData::matchesMarginFilter($margin, $filters['profit_margin'] ?? 'all')) {
                    return null;
                }

                return $this->formatBusinessRow($row);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, EarningLine>  $lines
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function buildAgencyRows(Collection $lines, array $filters): array
    {
        $agencyExpenses = $this->agencyExpenseTotals($filters);

        return $lines
            ->filter(fn (EarningLine $line) => $line->courier?->agency_id)
            ->groupBy(fn (EarningLine $line) => $line->courier->agency_id)
            ->map(function (Collection $agencyLines, int $agencyId) use ($agencyExpenses, $filters): ?array {
                $agency = $agencyLines->first()?->courier?->agency;

                if ($agency === null) {
                    return null;
                }

                $totalEarning = round((float) $agencyLines->sum('agency_payment'), 2);
                $totalCost = round((float) ($agencyExpenses[$agencyId] ?? 0), 2);
                $netProfit = round($totalEarning - $totalCost, 2);

                $row = [
                    'agency_id' => $agency->id,
                    'agency_name' => $agency->displayName(),
                    'city' => $agency->city?->name ?? '—',
                    'courier_count' => $agencyLines->pluck('courier_id')->unique()->count(),
                    'total_packages' => (int) $agencyLines->sum('package_count'),
                    'total_earning' => $totalEarning,
                    'total_cost' => $totalCost,
                    'net_profit' => $netProfit,
                    'total_earning_formatted' => MoneyCalculator::format($totalEarning),
                    'total_cost_formatted' => MoneyCalculator::format($totalCost),
                    'net_profit_formatted' => MoneyCalculator::format($netProfit),
                ];

                if (($filters['agency_id'] ?? 'all') !== 'all' && (int) $filters['agency_id'] !== $agencyId) {
                    return null;
                }

                if (($filters['city'] ?? 'all') !== 'all' && $row['city'] !== $filters['city']) {
                    return null;
                }

                return $row;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, EarningLine>  $lines
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function buildCourierRows(Collection $lines, array $filters): array
    {
        return $lines
            ->groupBy('courier_id')
            ->map(function (Collection $courierLines) use ($filters): ?array {
                /** @var EarningLine $first */
                $first = $courierLines->first();
                $courier = $first->courier;
                $business = $first->business;

                if ($courier === null || $business === null) {
                    return null;
                }

                $earning = round((float) $courierLines->sum('courier_total'), 2);
                $extraPayment = round((float) $courierLines->sum('extra_payment'), 2);
                $deduction = round((float) $courierLines->sum('deduction'), 2);
                $totalCost = round((float) $courierLines->sum('net_courier_payment'), 2);
                $pricingModel = ProfitabilityFormData::normalizePricingModel($first->pricing_model);

                $row = [
                    'courier_id' => $courier->id,
                    'courier_name' => $courier->full_name,
                    'agency_id' => $courier->agency_id,
                    'business_id' => $business->id,
                    'business_name' => $business->company_name,
                    'city' => $business->city?->name ?? '—',
                    'pricing_model' => $pricingModel,
                    'package_count' => (int) $courierLines->sum('package_count'),
                    'earning' => $earning,
                    'extra_payment' => $extraPayment,
                    'deduction' => $deduction,
                    'total_cost' => $totalCost,
                    'earning_formatted' => MoneyCalculator::format($earning),
                    'extra_payment_formatted' => MoneyCalculator::format($extraPayment),
                    'deduction_formatted' => MoneyCalculator::format($deduction),
                    'total_cost_formatted' => MoneyCalculator::format($totalCost),
                ];

                if (($filters['courier_id'] ?? 'all') !== 'all' && (int) $filters['courier_id'] !== $courier->id) {
                    return null;
                }

                if (($filters['pricing_model'] ?? 'all') !== 'all' && $pricingModel !== $filters['pricing_model']) {
                    return null;
                }

                return $row;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, EarningLine>  $lines
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function buildOperationRows(Collection $lines, array $filters): array
    {
        return $lines
            ->groupBy(fn (EarningLine $line) => $line->courier_id.'-'.$line->business_id)
            ->map(function (Collection $operationLines) use ($filters): ?array {
                /** @var EarningLine $first */
                $first = $operationLines->first();
                $courier = $first->courier;
                $business = $first->business;

                if ($courier === null || $business === null) {
                    return null;
                }

                $revenue = round((float) $operationLines->sum('revenue_total'), 2);
                $earning = round((float) $operationLines->sum('courier_total'), 2);
                $extraPayment = round((float) $operationLines->sum('extra_payment'), 2);
                $deduction = round((float) $operationLines->sum('deduction'), 2);
                $totalCost = round((float) $operationLines->sum('net_courier_payment'), 2);
                $netProfit = round($revenue - $totalCost, 2);
                $pricingModel = ProfitabilityFormData::normalizePricingModel($first->pricing_model);

                if (($filters['courier_id'] ?? 'all') !== 'all' && (int) $filters['courier_id'] !== $courier->id) {
                    return null;
                }

                if (($filters['business_id'] ?? 'all') !== 'all' && (int) $filters['business_id'] !== $business->id) {
                    return null;
                }

                if (($filters['pricing_model'] ?? 'all') !== 'all' && $pricingModel !== $filters['pricing_model']) {
                    return null;
                }

                return [
                    'courier_id' => $courier->id,
                    'courier_name' => $courier->full_name,
                    'agency_id' => $courier->agency_id,
                    'business_id' => $business->id,
                    'business_name' => $business->company_name,
                    'city' => $business->city?->name ?? '—',
                    'pricing_model' => $pricingModel,
                    'package_count' => (int) $operationLines->sum('package_count'),
                    'earning' => $earning,
                    'extra_payment' => $extraPayment,
                    'deduction' => $deduction,
                    'total_cost' => $totalCost,
                    'earning_formatted' => MoneyCalculator::format($earning),
                    'extra_payment_formatted' => MoneyCalculator::format($extraPayment),
                    'deduction_formatted' => MoneyCalculator::format($deduction),
                    'total_cost_formatted' => MoneyCalculator::format($totalCost),
                    'operation_label' => $courier->full_name.' — '.$business->company_name,
                    'revenue' => $revenue,
                    'net_profit' => $netProfit,
                    'revenue_formatted' => MoneyCalculator::format($revenue),
                    'net_profit_formatted' => MoneyCalculator::format($netProfit),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $businessRows
     * @param  array<int, array<string, mixed>>  $operationRows
     * @param  array<int, array<string, mixed>>  $agencyRows
     * @return array<string, mixed>
     */
    private function buildKpis(array $businessRows, array $operationRows, array $agencyRows): array
    {
        $totalRevenue = round(collect($businessRows)->sum('revenue'), 2);
        $courierCost = round(collect($businessRows)->sum('courier_cost'), 2);
        $agencyCost = round(collect($businessRows)->sum('agency_cost'), 2);
        $otherExpenses = round(collect($businessRows)->sum('other_expenses'), 2);
        $totalExpense = round($courierCost + $agencyCost + $otherExpenses, 2);
        $netProfit = round($totalRevenue - $totalExpense, 2);
        $margin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0.0;
        $totalPackages = (int) collect($businessRows)->sum('package_count');
        $perPackage = $totalPackages > 0 ? round($netProfit / $totalPackages, 2) : 0.0;

        $topBusiness = collect($businessRows)->sortByDesc('net_profit')->first();
        $topAgency = collect($agencyRows)->sortByDesc('net_profit')->first();
        $topOperation = collect($operationRows)->sortByDesc('net_profit')->first();

        return [
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => MoneyCalculator::format($totalRevenue),
            'total_expense' => $totalExpense,
            'total_expense_formatted' => MoneyCalculator::format($totalExpense),
            'net_profit' => $netProfit,
            'net_profit_formatted' => MoneyCalculator::format($netProfit),
            'profit_margin' => $margin,
            'profit_margin_formatted' => number_format($margin, 1, ',', '.').'%',
            'profit_per_package' => $perPackage,
            'profit_per_package_formatted' => MoneyCalculator::format($perPackage),
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
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function buildCharts(array $businessRows, array $agencyRows, array $filters): array
    {
        [$start, $end] = $this->resolveDateRange($filters['date_range'] ?? 'month');

        $months = [];
        $revenueSeries = [];
        $expenseSeries = [];

        if ($start && $end) {
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $monthLines = EarningLine::query()
                    ->where('period_year', $cursor->year)
                    ->where('period_month', $cursor->month)
                    ->when(($filters['business_id'] ?? 'all') !== 'all', fn (Builder $query) => $query->where('business_id', (int) $filters['business_id']))
                    ->get();

                $monthRevenue = (float) $monthLines->sum('revenue_total');
                $monthExpense = (float) $monthLines->sum('courier_total')
                    + (float) $monthLines->sum('agency_payment')
                    + (float) $monthLines->sum('extra_expense');

                $months[] = $cursor->translatedFormat('M');
                $revenueSeries[] = (int) round($monthRevenue);
                $expenseSeries[] = (int) round($monthExpense);

                $cursor->addMonth();
            }
        }

        $profitSeries = array_map(fn (int $revenue, int $expense) => $revenue - $expense, $revenueSeries, $expenseSeries);

        $businessChart = collect($businessRows)
            ->sortByDesc('net_profit')
            ->take(8)
            ->map(fn (array $row) => ['label' => $row['business_name'], 'value' => (int) round($row['net_profit'])])
            ->values()
            ->all();

        $agencyChart = collect($agencyRows)
            ->sortByDesc('net_profit')
            ->take(8)
            ->map(fn (array $row) => ['label' => $row['agency_name'], 'value' => (int) round($row['net_profit'])])
            ->values()
            ->all();

        $cityChart = collect($businessRows)
            ->groupBy('city')
            ->map(fn (Collection $group, string $city) => [
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
                'revenue' => $revenueSeries,
                'expense' => $expenseSeries,
                'profit' => $profitSeries,
            ],
            'business_profitability' => $businessChart,
            'agency_profitability' => $agencyChart,
            'city_profitability' => $cityChart,
            'revenue_distribution' => array_values(array_filter($revenueDistribution, fn (array $item) => $item['value'] > 0)),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatBusinessRow(array $row): array
    {
        return array_merge($row, [
            'revenue_formatted' => MoneyCalculator::format($row['revenue']),
            'courier_cost_formatted' => MoneyCalculator::format($row['courier_cost']),
            'agency_cost_formatted' => MoneyCalculator::format($row['agency_cost']),
            'other_expenses_formatted' => MoneyCalculator::format($row['other_expenses']),
            'net_profit_formatted' => MoneyCalculator::format($row['net_profit']),
            'profit_margin_formatted' => number_format($row['profit_margin'], 1, ',', '.').'%',
            'profit_per_package_formatted' => MoneyCalculator::format($row['profit_per_package']),
        ]);
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<int, float>
     */
    private function agencyExpenseTotals(array $filters): array
    {
        $query = FinanceExpense::query()
            ->where('expense_type', 'agency_earning')
            ->whereNotNull('agency_id');

        $this->applyExpenseDateRange($query, $filters['date_range'] ?? 'month');

        return $query
            ->selectRaw('agency_id, SUM(amount) as total')
            ->groupBy('agency_id')
            ->pluck('total', 'agency_id')
            ->map(fn ($total) => (float) $total)
            ->all();
    }

    private function applyDateRange(Builder $query, string $range): void
    {
        [$start, $end] = $this->resolveDateRange($range);

        if ($start === null || $end === null) {
            return;
        }

        $startMonth = $start->year * 12 + $start->month;
        $endMonth = $end->year * 12 + $end->month;

        $query->whereRaw('(period_year * 12 + period_month) BETWEEN ? AND ?', [$startMonth, $endMonth]);
    }

    private function applyExpenseDateRange(Builder $query, string $range): void
    {
        [$start, $end] = $this->resolveDateRange($range);

        if ($start === null || $end === null) {
            return;
        }

        $query->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()]);
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveDateRange(string $range): array
    {
        $today = Carbon::today();

        return match ($range) {
            'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'quarter' => [$today->copy()->firstOfQuarter(), $today->copy()->lastOfQuarter()],
            'year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'last_6_months' => [$today->copy()->subMonths(5)->startOfMonth(), $today->copy()->endOfMonth()],
            'all' => [null, null],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };
    }
}
