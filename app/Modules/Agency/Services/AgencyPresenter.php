<?php

namespace App\Modules\Agency\Services;

use App\Modules\Agency\Data\AgencyFormData;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyContact;
use App\Models\Contract;
use App\Models\Document;
use App\Modules\Agency\Support\AgencyFeatures;
use App\Modules\Agency\Support\AgencyLogo;

class AgencyPresenter
{
    public function __construct(
        private readonly AgencyMediaService $media,
        private readonly AgencyContractService $contracts,
        private readonly AgencyContractPresenter $contractPresenter,
        private readonly AgencyDocumentService $documents,
        private readonly AgencyDocumentPresenter $documentPresenter,
        private readonly AgencyEarningService $earnings,
        private readonly AgencyContactService $contacts,
        private readonly AgencyContactPresenter $contactPresenter,
        private readonly AgencyCourierService $couriers,
        private readonly AgencyCourierPresenter $courierPresenter,
        private readonly AgencyActivityService $activities,
        private readonly AgencyActivityPresenter $activityPresenter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toBaseArray(Agency $agency): array
    {
        $agency->loadMissing(['city', 'district']);
        $logo = AgencyLogo::initials($agency);

        return array_merge([
            'id' => $agency->id,
            'company_name' => $agency->company_name,
            'brand_name' => $agency->brand_name,
            'display_name' => $agency->displayName(),
            'phone' => $agency->phone,
            'tax_number' => $agency->tax_number,
            'city' => $agency->city?->name ?? '',
            'district' => $agency->district?->name ?? '',
            'authorized_person' => $agency->authorized_person,
            'status' => $agency->status,
            'notes' => $agency->notes,
            'active_couriers' => $agency->activeCourierCount(),
            'active_businesses' => 0,
            'monthly_earning' => 0.0,
            'logo_path' => $agency->logo_path,
            'logo_url' => $this->media->url($agency->logo_path),
            'has_logo_image' => ! empty($agency->logo_path),
        ], $logo);
    }

    /**
     * @return array<string, mixed>
     */
    public function indexRow(Agency $agency): array
    {
        $base = $this->toBaseArray($agency);
        $statusLabels = AgencyFormData::statuses();

        return array_merge($base, [
            'location' => trim($base['city'].' / '.$base['district'], ' /'),
            'status_label' => $statusLabels[$base['status']] ?? $base['status'],
            'monthly_earning_formatted' => '0,00 ₺',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(Agency $agency): array
    {
        $base = $this->toBaseArray($agency);
        $id = $agency->id;
        $statusLabels = AgencyFormData::statuses();

        return array_merge([
            'id' => $id,
            'logo' => $base['logo'],
            'logo_color' => $base['logo_color'],
            'logo_url' => $base['logo_url'],
            'has_logo_image' => $base['has_logo_image'],
            'company_name' => $base['company_name'],
            'brand_name' => $base['brand_name'],
            'display_name' => $base['display_name'] ?? $base['brand_name'] ?? $base['company_name'],
            'authorized_person' => $base['authorized_person'] ?? '—',
            'phone' => $base['phone'],
            'location' => trim($base['city'].' / '.$base['district'], ' /'),
            'active_couriers' => $base['active_couriers'],
            'active_businesses' => $base['active_businesses'],
            'status_label' => $statusLabels[$base['status']] ?? $base['status'],
            'contacts_url' => route('agencies.contacts.index', ['agency_id' => $id]),
            'couriers_url' => route('agencies.couriers.index', ['agency_id' => $id]),
            'contracts_url' => route('agencies.contracts.index', ['agency_id' => $id]),
            'documents_url' => route('agencies.documents.index', ['agency_id' => $id]),
            'activities_url' => route('agencies.activities.index', ['agency_id' => $id]),
        ], AgencyFeatures::earningsEnabled() ? [
            'monthly_earning_formatted' => $base['monthly_earning_formatted'] ?? '0,00 ₺',
            'earnings_url' => route('agencies.earnings.index', ['agency_id' => $id]),
        ] : []);
    }

    /**
     * @return array<string, mixed>
     */
    public function showPayload(Agency $agency): array
    {
        $base = $this->toBaseArray($agency);
        $id = $agency->id;

        return array_merge($this->detailPayload($agency), [
            'status' => $base['status'],
            'uuid' => $agency->uuid,
            'public_id' => $agency->public_id,
            'tax_number' => $base['tax_number'],
            'brand_name' => $base['brand_name'] ?? $base['company_name'],
            'notes' => $base['notes'],
            'created_at_formatted' => $agency->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
            'contacts' => $this->contacts
                ->forAgency($id)
                ->map(fn (AgencyContact $contact) => $this->contactPresenter->showRow($contact))
                ->values()
                ->all(),
            'couriers' => $this->couriers
                ->filter(['agency_id' => (string) $id])
                ->values()
                ->all(),
            'contracts' => $this->contracts
                ->forAgency($id)
                ->map(fn (Contract $contract) => $this->contractPresenter->showRow($contract))
                ->values()
                ->all(),
            'documents' => $this->documents
                ->forAgency($id)
                ->map(fn (Document $document) => $this->documentPresenter->indexRow($document))
                ->values()
                ->all(),
            'activities' => $this->activities
                ->forAgency($id)
                ->map(fn (ActivityLog $log) => $this->activityPresenter->indexRow($log))
                ->values()
                ->all(),
        ], AgencyFeatures::earningsEnabled() ? [
            'monthly_earning' => '0,00 ₺',
            'earnings' => $this->earnings
                ->filter(['agency_id' => $agency->id])
                ->values()
                ->all(),
        ] : []);
    }

    /**
     * @return array<string, mixed>
     */
    public function formPayload(Agency $agency): array
    {
        $base = $this->toBaseArray($agency);

        return [
            'company_name' => $base['company_name'],
            'brand_name' => $base['brand_name'],
            'phone' => $base['phone'],
            'tax_number' => $base['tax_number'],
            'city' => $base['city'],
            'district' => $base['district'],
            'status' => $base['status'],
            'notes' => $base['notes'],
            'logo_url' => $base['logo_url'],
        ];
    }
}
