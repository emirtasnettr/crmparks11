<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Agency\Data\AgencyDummyData;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Services\BusinessPresenter;
use App\Modules\Courier\Data\CourierDummyData;

class DashboardService
{
    public function __construct(
        private readonly BusinessPresenter $businessPresenter,
    ) {}

    public function getStats(): array
    {
        $courierSummary = CourierDummyData::summary([]);
        $agencySummary = AgencyDummyData::summary([]);

        return [
            'total_businesses' => Business::query()->count(),
            'total_couriers' => $courierSummary['total'],
            'total_agencies' => $agencySummary['total'],
            'active_couriers' => $courierSummary['active'],
            'inactive_couriers' => $courierSummary['total'] - $courierSummary['active'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLatestBusinesses(int $limit = 5): array
    {
        return Business::query()
            ->with(['city', 'district', 'activePricing.pricingModelType'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Business $business) => $this->formatBusinessForDashboard(
                $this->businessPresenter->toBaseArray($business),
                $business,
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLatestCouriers(int $limit = 5): array
    {
        return collect(CourierDummyData::all())
            ->sortByDesc('id')
            ->take($limit)
            ->map(fn (array $courier) => $this->formatCourierForDashboard($courier))
            ->values()
            ->all();
    }

    /**
     * @return array{total: int, items: array<int, array<string, mixed>>}
     */
    public function getCourierTypeDistribution(): array
    {
        $couriers = CourierDummyData::all();
        $total = count($couriers);

        $items = collect(CourierDummyData::courierTypes())
            ->map(function (string $label, string $key) use ($couriers, $total) {
                $count = collect($couriers)->where('courier_type', $key)->count();

                return [
                    'key' => $key,
                    'label' => $label,
                    'count' => $count,
                    'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();

        return [
            'total' => $total,
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $business
     * @return array<string, mixed>
     */
    private function formatBusinessForDashboard(array $business, Business $model): array
    {
        $id = (int) $business['id'];

        $pricingLabels = [
            'per_package' => 'Paket Başı',
            'fixed' => 'Sabit Ücret',
            'monthly_fixed' => 'Aylık Sabit',
            'hourly' => 'Saatlik',
            'daily' => 'Günlük',
        ];

        return [
            'id' => $id,
            'company_name' => $business['company_name'],
            'brand_name' => $business['brand_name'],
            'logo' => $business['logo'],
            'logo_color' => $business['logo_color'],
            'logo_url' => $business['logo_url'] ?? null,
            'location' => trim($business['city'].' / '.$business['district'], ' /'),
            'pricing_model_label' => $pricingLabels[$business['pricing_model']] ?? $business['pricing_model'],
            'status' => $business['status'],
            'created_at_formatted' => $model->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
            'url' => route('businesses.show', $id),
        ];
    }

    /**
     * @param  array<string, mixed>  $courier
     * @return array<string, mixed>
     */
    private function formatCourierForDashboard(array $courier): array
    {
        $id = (int) $courier['id'];

        return [
            'id' => $id,
            'full_name' => $courier['full_name'],
            'avatar_initials' => $courier['avatar_initials'],
            'avatar_color' => $courier['avatar_color'],
            'photo_url' => $courier['photo_url'] ?? null,
            'courier_type' => $courier['courier_type'],
            'type_label' => $courier['courier_type'] === 'agency'
                ? ($courier['agency_name'] ?? CourierDummyData::courierTypes()['agency'])
                : CourierDummyData::courierTypes()['independent'],
            'vehicle_type_label' => $courier['vehicle_type_label'],
            'status' => $courier['status'],
            'created_at_formatted' => now()->subDays(max(1, 90 - ($id * 2)))->format('d.m.Y'),
            'url' => route('couriers.show', $id),
        ];
    }
}
