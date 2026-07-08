<?php

namespace App\Modules\Business\Data;

use App\Core\Helpers\MoneyCalculator;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class BusinessOverviewStats
{
    /**
     * @return array{start_date: string, end_date: string, start_date_formatted: string, end_date_formatted: string}
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
        $business = BusinessDummyData::find($businessId);

        if ($business === null) {
            return self::emptyStats();
        }

        $daily = self::dailyPackageRows($businessId, $business, $start, $end);
        $totalPackages = (int) collect($daily)->sum('package_count');
        $totalRevenue = round(collect($daily)->sum('revenue_total'), 2);
        $totalCourier = round(collect($daily)->sum('courier_total'), 2);

        $receivedPerPackage = $totalPackages > 0
            ? round($totalRevenue / $totalPackages, 2)
            : 0.0;

        $courierPerPackage = $totalPackages > 0
            ? round($totalCourier / $totalPackages, 2)
            : 0.0;

        $netPerPackage = round($receivedPerPackage - $courierPerPackage, 2);

        return [
            'received_per_package' => $receivedPerPackage,
            'courier_per_package' => $courierPerPackage,
            'active_couriers' => self::activeCouriersInPeriod($businessId, $start, $end),
            'net_per_package' => $netPerPackage,
            'total_packages' => $totalPackages,
            'received_per_package_formatted' => MoneyCalculator::format($receivedPerPackage),
            'courier_per_package_formatted' => MoneyCalculator::format($courierPerPackage),
            'net_per_package_formatted' => MoneyCalculator::format($netPerPackage),
        ];
    }

    /**
     * @param  array<string, mixed>  $business
     * @return array<int, array<string, float|int|string>>
     */
    private static function dailyPackageRows(int $businessId, array $business, CarbonInterface $start, CarbonInterface $end): array
    {
        $unitPrices = BusinessDummyData::unitPrices($businessId, $business);
        $useExactPrices = $unitPrices['from_profile'];
        $rows = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $seed = crc32($businessId.'-'.$cursor->toDateString());
            $weekendFactor = in_array($cursor->dayOfWeekIso, [6, 7], true) ? 1.18 : 1.0;
            $packageCount = (int) round((90 + ($seed % 95)) * $weekendFactor);
            $revenueUnit = $useExactPrices
                ? $unitPrices['revenue_unit']
                : round($unitPrices['revenue_unit'] + (($seed % 5) - 2) * 0.5, 2);
            $courierUnit = $useExactPrices
                ? $unitPrices['courier_unit']
                : round($unitPrices['courier_unit'] + (($seed % 4) - 1) * 0.5, 2);

            $rows[] = [
                'date' => $cursor->toDateString(),
                'package_count' => max(0, $packageCount),
                'revenue_unit_price' => max(0, $revenueUnit),
                'courier_unit_price' => max(0, $courierUnit),
                'revenue_total' => round(max(0, $packageCount) * max(0, $revenueUnit), 2),
                'courier_total' => round(max(0, $packageCount) * max(0, $courierUnit), 2),
            ];

            $cursor->addDay();
        }

        return $rows;
    }

    private static function activeCouriersInPeriod(int $businessId, CarbonInterface $start, CarbonInterface $end): int
    {
        return collect(BusinessAssignmentDummyData::all())
            ->filter(function (array $assignment) use ($businessId, $start, $end) {
                if ((int) $assignment['business_id'] !== $businessId) {
                    return false;
                }

                $assignmentStart = Carbon::parse($assignment['start_date'])->startOfDay();
                $assignmentEnd = $assignment['end_date']
                    ? Carbon::parse($assignment['end_date'])->endOfDay()
                    : $end->copy()->endOfDay();

                return $assignmentStart->lte($end) && $assignmentEnd->gte($start);
            })
            ->pluck('courier_id')
            ->unique()
            ->count();
    }

    /**
     * @return array<string, int|float|string>
     */
    private static function emptyStats(): array
    {
        return [
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
