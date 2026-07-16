<?php

namespace App\Modules\Courier\Services;

use App\Models\EarningLine;
use App\Modules\Courier\Data\CourierFormData;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Support\CourierAvatar;
use App\Modules\Courier\Support\CourierFeatures;

class CourierPresenter
{
    public function __construct(
        private readonly CourierMediaService $media,
        private readonly CourierEarningService $earnings,
        private readonly CourierEarningPresenter $earningPresenter,
        private readonly CourierDocumentService $documents,
        private readonly CourierDocumentPresenter $documentPresenter,
        private readonly CourierWorkHistoryService $workHistory,
        private readonly CourierWorkHistoryPresenter $workHistoryPresenter,
        private readonly CourierVehicleService $vehicles,
        private readonly CourierVehiclePresenter $vehiclePresenter,
        private readonly CourierBankAccountService $bankAccounts,
        private readonly CourierBankAccountPresenter $bankAccountPresenter,
        private readonly CourierActivityService $activities,
        private readonly CourierActivityPresenter $activityPresenter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toBaseArray(Courier $courier): array
    {
        $courier->loadMissing(['city', 'district', 'agency', 'vehicleType']);
        $avatar = CourierAvatar::forCourier($courier);
        $vehicleCode = $this->vehicleTypeCode($courier);

        return array_merge([
            'id' => $courier->id,
            'first_name' => $courier->first_name,
            'last_name' => $courier->last_name,
            'full_name' => $courier->full_name,
            'phone' => $courier->phone,
            'email' => $courier->email,
            'tc_number' => $courier->tc_number,
            'birth_date' => $courier->birth_date?->format('Y-m-d'),
            'courier_type' => $courier->courier_type,
            'agency_id' => $courier->agency_id,
            'agency_name' => $courier->agency?->displayName(),
            'vehicle_type' => $vehicleCode,
            'vehicle_type_label' => CourierFormData::vehicleTypes()[$vehicleCode] ?? '—',
            'courier_type_label' => CourierFormData::courierTypes()[$courier->courier_type] ?? '—',
            'tax_office' => $courier->tax_office,
            'tax_number' => $courier->tax_number,
            'company_name' => $courier->company_name,
            'city' => $courier->city?->name ?? '',
            'district' => $courier->district?->name ?? '',
            'address' => $courier->address,
            'plate' => $courier->plate,
            'vehicle_brand' => $courier->vehicle_brand,
            'vehicle_model' => $courier->vehicle_model,
            'bank_name' => $courier->bank_name,
            'iban' => $courier->iban,
            'account_holder' => $courier->account_holder,
            'start_date' => $courier->start_date?->format('Y-m-d'),
            'status' => $courier->status,
            'notes' => $courier->notes,
            'active_business_name' => null,
            'photo_path' => $courier->photo_path,
            'photo_url' => $this->media->url($courier->photo_path),
            'has_profile_photo' => ! empty($courier->photo_path),
            'can_delete' => auth()->user()?->hasRole('super_admin') ?? false,
        ], $avatar);
    }

    /**
     * @return array<string, mixed>
     */
    public function indexRow(Courier $courier): array
    {
        return $this->toBaseArray($courier);
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(Courier $courier): array
    {
        $base = $this->toBaseArray($courier);
        $id = $courier->id;
        $statusLabels = CourierFormData::statuses();

        return array_merge([
            'id' => $id,
            'avatar_initials' => $base['avatar_initials'],
            'avatar_color' => $base['avatar_color'],
            'photo_url' => $base['photo_url'],
            'has_profile_photo' => $base['has_profile_photo'],
            'full_name' => $base['full_name'],
            'agency_name' => $base['agency_name'],
            'phone' => $base['phone'],
            'courier_type_label' => $base['courier_type_label'],
            'vehicle_type_label' => $base['vehicle_type_label'],
            'active_business_name' => $base['active_business_name'],
            'status_label' => $statusLabels[$base['status']] ?? $base['status'],
            'work_history_url' => route('couriers.work-history.index', ['courier_id' => $id]),
            'documents_url' => route('couriers.documents.index', ['courier_id' => $id]),
            'bank_accounts_url' => route('couriers.bank-accounts.index', ['courier_id' => $id]),
        ], CourierFeatures::earningsEnabled() ? [
            'earnings_url' => route('couriers.earnings.index', ['courier_id' => $id]),
        ] : []);
    }

    /**
     * @return array<string, mixed>
     */
    public function showPayload(Courier $courier): array
    {
        $base = $this->toBaseArray($courier);
        $id = $courier->id;
        $vehicles = $this->vehicles->forCourier($id)
            ->map(fn ($vehicle) => $this->vehiclePresenter->indexRow($vehicle))
            ->values()
            ->all();
        $activeVehicle = collect($vehicles)->firstWhere('status', 'active') ?? $vehicles[0] ?? null;
        $bankAccountRows = $this->bankAccounts->forCourier($id)
            ->map(fn ($account) => $this->bankAccountPresenter->indexRow($account))
            ->values()
            ->all();
        $defaultBank = collect($bankAccountRows)->firstWhere('is_default', true) ?? $bankAccountRows[0] ?? null;

        return array_merge($this->detailPayload($courier), [
            'status' => $base['status'],
            'uuid' => $courier->uuid,
            'tc_number' => $base['tc_number'],
            'email' => $base['email'],
            'birth_date_formatted' => $courier->birth_date?->format('d.m.Y'),
            'city' => $base['city'],
            'district' => $base['district'],
            'address' => $base['address'],
            'tax_office' => $courier->courier_type === 'independent' ? $base['tax_office'] : null,
            'tax_number' => $courier->courier_type === 'independent' ? $base['tax_number'] : null,
            'company_name' => $courier->courier_type === 'independent' ? $base['company_name'] : null,
            'start_date_formatted' => $courier->start_date?->format('d.m.Y'),
            'notes' => $base['notes'],
            'active_vehicle' => $activeVehicle,
            'default_bank' => $defaultBank,
            'vehicles' => $vehicles,
            'bank_accounts' => $bankAccountRows,
            'documents' => $this->documents->forCourier($id)
                ->map(fn ($document) => $this->documentPresenter->indexRow($document))
                ->values()
                ->all(),
            'work_history' => $this->workHistory->filter(['courier_id' => $id])
                ->map(fn ($assignment) => $this->workHistoryPresenter->indexRow($assignment))
                ->values()
                ->all(),
            'activities' => $this->activities->forCourier($id)
                ->map(fn ($log) => $this->activityPresenter->indexRow($log))
                ->values()
                ->all(),
            'vehicles_url' => route('couriers.vehicles.index', ['courier_id' => $id]),
            'activities_url' => route('couriers.activities.index', ['courier_id' => $id]),
        ], CourierFeatures::earningsEnabled() ? [
            'earnings' => $this->earnings
                ->forCourier($courier->id)
                ->map(fn (EarningLine $line) => $this->earningPresenter->showRow($line))
                ->values()
                ->all(),
        ] : []);
    }

    /**
     * @return array<string, mixed>
     */
    public function formPayload(Courier $courier): array
    {
        $base = $this->toBaseArray($courier);

        return [
            'first_name' => $base['first_name'],
            'last_name' => $base['last_name'],
            'tc_number' => $base['tc_number'],
            'birth_date' => $base['birth_date'],
            'phone' => $base['phone'],
            'email' => $base['email'],
            'courier_type' => $base['courier_type'],
            'agency_id' => $base['agency_id'] ? (string) $base['agency_id'] : '',
            'tax_office' => $base['tax_office'] ?? '',
            'tax_number' => $base['tax_number'] ?? '',
            'company_name' => $base['company_name'] ?? '',
            'city' => $base['city'],
            'district' => $base['district'],
            'address' => $base['address'],
            'vehicle_type' => $base['vehicle_type'],
            'plate' => $base['plate'] ?? '',
            'vehicle_brand' => $base['vehicle_brand'] ?? '',
            'vehicle_model' => $base['vehicle_model'] ?? '',
            'bank_name' => $base['bank_name'] ?? '',
            'iban' => $base['iban'] ?? '',
            'account_holder' => $base['account_holder'] ?? '',
            'start_date' => $base['start_date'],
            'status' => $base['status'],
            'notes' => $base['notes'],
            'photo_url' => $base['photo_url'],
        ];
    }

    private function vehicleTypeCode(Courier $courier): string
    {
        $code = $courier->vehicleType?->code ?? 'motor';

        return match ($code) {
            'motor' => 'motorcycle',
            'car' => 'car',
            'bicycle' => 'bicycle',
            'pedestrian' => 'pedestrian',
            default => 'motorcycle',
        };
    }
}
