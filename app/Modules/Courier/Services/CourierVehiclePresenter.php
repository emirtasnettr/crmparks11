<?php

namespace App\Modules\Courier\Services;

use App\Modules\Courier\Data\CourierVehicleFormData;
use App\Modules\Courier\Models\CourierVehicle;
use Carbon\Carbon;

class CourierVehiclePresenter
{
    public const INSURANCE_WARNING_DAYS = 30;

    /**
     * @return array<string, mixed>
     */
    public function indexRow(CourierVehicle $vehicle): array
    {
        return $this->enrich($vehicle);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(CourierVehicle $vehicle): array
    {
        return $this->enrich($vehicle);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(CourierVehicle $vehicle): array
    {
        $vehicle->loadMissing('courier');

        $courier = $vehicle->courier;
        $today = Carbon::today();
        $vehicleType = $vehicle->vehicle_type;
        $requiresVehicleDocs = $vehicleType !== 'pedestrian';

        $licenseStatus = $requiresVehicleDocs
            ? (! empty($vehicle->license_number) ? 'valid' : 'missing')
            : null;

        $insuranceStatus = null;
        $insuranceExpiryFormatted = '—';

        if ($requiresVehicleDocs && $vehicle->insurance_expiry_date) {
            $expiry = $vehicle->insurance_expiry_date->copy()->startOfDay();
            $insuranceExpiryFormatted = $expiry->format('d.m.Y');
            $daysRemaining = $today->diffInDays($expiry, false);

            if ($daysRemaining < 0) {
                $insuranceStatus = 'expired';
            } elseif ($daysRemaining <= self::INSURANCE_WARNING_DAYS) {
                $insuranceStatus = 'expiring_soon';
            } else {
                $insuranceStatus = 'valid';
            }
        } elseif ($requiresVehicleDocs && ! $vehicle->insurance_expiry_date) {
            $insuranceStatus = 'missing';
        }

        $registeredAt = $vehicle->registered_at ?? $vehicle->created_at ?? now();

        return [
            'id' => $vehicle->id,
            'uuid' => $vehicle->uuid,
            'courier_id' => $vehicle->courier_id,
            'courier_name' => $courier?->full_name ?? '—',
            'courier_phone' => $courier?->phone ?? '—',
            'courier_type' => $courier?->courier_type ?? 'independent',
            'vehicle_type' => $vehicleType,
            'vehicle_type_label' => CourierVehicleFormData::vehicleTypes()[$vehicleType] ?? '—',
            'plate' => $vehicle->plate,
            'plate_formatted' => $vehicle->plate ?? '—',
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'model_year' => $vehicle->model_year,
            'color' => $vehicle->color,
            'model_year_formatted' => $vehicle->model_year ? (string) $vehicle->model_year : '—',
            'brand_formatted' => $vehicle->brand ?? '—',
            'model_formatted' => $vehicle->model ?? '—',
            'color_formatted' => $vehicle->color ?? '—',
            'license_number' => $vehicle->license_number,
            'insurance_policy_number' => $vehicle->insurance_policy_number,
            'insurance_expiry_date' => $vehicle->insurance_expiry_date?->toDateString(),
            'insurance_expiry_formatted' => $insuranceExpiryFormatted,
            'insurance_expiry_date_formatted' => $insuranceExpiryFormatted,
            'license_status' => $licenseStatus,
            'insurance_status' => $insuranceStatus,
            'status' => $vehicle->status,
            'status_label' => CourierVehicleFormData::statuses()[$vehicle->status] ?? '—',
            'registered_at' => $registeredAt->toDateString(),
            'registered_at_formatted' => $registeredAt->format('d.m.Y'),
            'notes' => $vehicle->notes,
            'requires_vehicle_docs' => $requiresVehicleDocs,
            'history' => $this->buildHistory($vehicle, $registeredAt),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildHistory(CourierVehicle $vehicle, Carbon $registeredAt): array
    {
        $events = [
            [
                'date' => $registeredAt->toDateString(),
                'date_formatted' => $registeredAt->format('d.m.Y'),
                'label' => 'Araç kaydedildi',
                'detail' => (CourierVehicleFormData::vehicleTypes()[$vehicle->vehicle_type] ?? $vehicle->vehicle_type)
                    .' — '.($vehicle->plate ?? 'Plakasız'),
            ],
        ];

        if ($vehicle->insurance_policy_number && $vehicle->insurance_expiry_date) {
            $policyDate = $vehicle->insurance_expiry_date->copy()->subYear();

            $events[] = [
                'date' => $policyDate->toDateString(),
                'date_formatted' => $policyDate->format('d.m.Y'),
                'label' => 'Sigorta poliçesi tanımlandı',
                'detail' => $vehicle->insurance_policy_number,
            ];
        }

        if ($vehicle->status === 'inactive') {
            $inactiveDate = $registeredAt->copy()->addMonths(6);

            $events[] = [
                'date' => $inactiveDate->toDateString(),
                'date_formatted' => $inactiveDate->format('d.m.Y'),
                'label' => 'Araç pasife alındı',
                'detail' => 'Kayıt silinmedi — geçmiş korunuyor.',
            ];
        }

        return collect($events)
            ->sortByDesc('date')
            ->values()
            ->all();
    }
}
