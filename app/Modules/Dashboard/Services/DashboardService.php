<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Services\BusinessPresenter;
use App\Modules\Courier\Data\CourierFormData;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Services\CourierPresenter;

class DashboardService
{
    public function __construct(
        private readonly BusinessPresenter $businessPresenter,
        private readonly CourierPresenter $courierPresenter,
    ) {}

    public function getStats(): array
    {
        $totalCouriers = Courier::query()->count();
        $activeCouriers = Courier::query()->where('status', 'active')->count();

        return [
            'total_businesses' => Business::query()->count(),
            'total_couriers' => $totalCouriers,
            'total_agencies' => Agency::query()->count(),
            'active_couriers' => $activeCouriers,
            'inactive_couriers' => $totalCouriers - $activeCouriers,
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
        return Courier::query()
            ->with(['city', 'district', 'agency', 'vehicleType'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Courier $courier) => $this->formatCourierForDashboard(
                $this->courierPresenter->toBaseArray($courier),
                $courier,
            ))
            ->values()
            ->all();
    }

    /**
     * @return array{total: int, items: array<int, array<string, mixed>>}
     */
    public function getCourierTypeDistribution(): array
    {
        $total = Courier::query()->count();

        $items = collect(CourierFormData::courierTypes())
            ->map(function (string $label, string $key) use ($total) {
                $count = Courier::query()->where('courier_type', $key)->count();

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
    private function formatCourierForDashboard(array $courier, Courier $model): array
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
                ? ($courier['agency_name'] ?? CourierFormData::courierTypes()['agency'])
                : CourierFormData::courierTypes()['independent'],
            'vehicle_type_label' => $courier['vehicle_type_label'],
            'status' => $courier['status'],
            'created_at_formatted' => $model->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
            'url' => route('couriers.show', $id),
        ];
    }
}
