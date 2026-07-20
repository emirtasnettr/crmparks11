<?php

namespace App\Modules\Business\Data;

use App\Core\Helpers\MoneyCalculator;
use App\Models\EarningLine;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Services\BusinessPresenter;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BusinessOverviewStats
{
    /**
     * @return array{start: CarbonInterface, end: CarbonInterface, start_date: string, end_date: string, start_date_formatted: string, end_date_formatted: string, range_label: string}
     */
    public static function resolveDateRange(?string $startDate, ?string $endDate): array
    {
        $end = $endDate
            ? Carbon::parse($endDate)->startOfDay()
            : now()->startOfDay();

        $start = $startDate
            ? Carbon::parse($startDate)->startOfDay()
            : $end->copy()->subDays(6);

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        return [
            'start' => $start,
            'end' => $end,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'start_date_formatted' => $start->format('d.m.Y'),
            'end_date_formatted' => $end->format('d.m.Y'),
            'range_label' => $start->format('d.m.Y').' – '.$end->format('d.m.Y'),
        ];
    }

    /**
     * @return array<string, int|float|string>
     */
    public static function forBusiness(int $businessId, CarbonInterface $start, CarbonInterface $end): array
    {
        $business = Business::query()
            ->with(['activeCommercialContract', 'city', 'district'])
            ->find($businessId);

        if ($business === null) {
            return self::emptyStats();
        }

        $presenter = app(BusinessPresenter::class);
        $unitPrices = $presenter->unitPrices($business);
        $pricingModel = $business->activeCommercialContract?->work_type ?? 'per_package';
        $lines = self::earningLinesInPeriod($businessId, $start, $end);
        $totalPackages = (int) $lines->sum('package_count');
        $totalRevenue = round((float) $lines->sum('revenue_total'), 2);
        $totalCourier = round((float) $lines->sum('courier_total'), 2);

        if ($pricingModel === 'per_package' && $totalPackages > 0) {
            $receivedPerPackage = round($totalRevenue / $totalPackages, 2);
            $courierPerPackage = round($totalCourier / $totalPackages, 2);
        } elseif ($unitPrices['from_profile']) {
            $receivedPerPackage = $unitPrices['revenue_unit'];
            $courierPerPackage = $unitPrices['courier_unit'];
        } elseif ($pricingModel === 'per_package') {
            $receivedPerPackage = 0.0;
            $courierPerPackage = 0.0;
        } else {
            $receivedPerPackage = $totalRevenue;
            $courierPerPackage = $totalCourier;
        }

        $netPerPackage = round($receivedPerPackage - $courierPerPackage, 2);
        $labels = BusinessFormData::overviewPricingLabels($pricingModel);

        return [
            'pricing_model' => $pricingModel,
            'labels' => $labels,
            'received_per_package' => $receivedPerPackage,
            'courier_per_package' => $courierPerPackage,
            'active_couriers' => self::currentActiveCouriers($businessId),
            'net_per_package' => $netPerPackage,
            'total_packages' => $totalPackages,
            'received_per_package_formatted' => MoneyCalculator::format($receivedPerPackage),
            'courier_per_package_formatted' => MoneyCalculator::format($courierPerPackage),
            'net_per_package_formatted' => MoneyCalculator::format($netPerPackage),
        ];
    }

    /**
     * @return Collection<int, EarningLine>
     */
    private static function earningLinesInPeriod(int $businessId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $months = [];
        $cursor = $start->copy()->startOfMonth();
        $endMonth = $end->copy()->startOfMonth();

        while ($cursor->lte($endMonth)) {
            $months[] = [
                'year' => (int) $cursor->format('Y'),
                'month' => (int) $cursor->format('n'),
            ];
            $cursor->addMonth();
        }

        if ($months === []) {
            return collect();
        }

        return EarningLine::query()
            ->where('business_id', $businessId)
            ->where(function (Builder $query) use ($months): void {
                foreach ($months as $period) {
                    $query->orWhere(function (Builder $inner) use ($period): void {
                        $inner->where('period_year', $period['year'])
                            ->where('period_month', $period['month']);
                    });
                }
            })
            ->get();
    }

    private static function currentActiveCouriers(int $businessId): int
    {
        $business = Business::query()->find($businessId);

        return $business?->activeCourierCount() ?? 0;
    }

    /**
     * @return array<string, int|float|string>
     */
    private static function emptyStats(): array
    {
        $labels = BusinessFormData::overviewPricingLabels('per_package');

        return [
            'pricing_model' => 'per_package',
            'labels' => $labels,
            'received_per_package' => 0,
            'courier_per_package' => 0,
            'active_couriers' => 0,
            'net_per_package' => 0,
            'total_packages' => 0,
            'received_per_package_formatted' => MoneyCalculator::format(0),
            'courier_per_package_formatted' => MoneyCalculator::format(0),
            'net_per_package_formatted' => MoneyCalculator::format(0),
        ];
    }
}
