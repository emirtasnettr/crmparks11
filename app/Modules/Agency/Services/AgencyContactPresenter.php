<?php

namespace App\Modules\Agency\Services;

use App\Modules\Agency\Data\AgencyContactFormData;
use App\Modules\Agency\Models\AgencyContact;

class AgencyContactPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(AgencyContact $contact): array
    {
        $contact->loadMissing('agency');
        $statuses = AgencyContactFormData::statuses();

        return [
            'id' => $contact->id,
            'agency_id' => $contact->agency_id,
            'agency_name' => $contact->agency?->company_name ?? '—',
            'full_name' => $contact->full_name,
            'title' => $contact->title,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'is_default' => (bool) $contact->is_default,
            'status' => $contact->status ?? 'active',
            'status_label' => $statuses[$contact->status ?? 'active'] ?? ($contact->status ?? 'active'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(AgencyContact $contact): array
    {
        $contact->loadMissing('agency');
        $statuses = AgencyContactFormData::statuses();

        return [
            'id' => $contact->id,
            'uuid' => $contact->uuid,
            'agency_id' => $contact->agency_id,
            'agency_name' => $contact->agency?->company_name ?? '—',
            'full_name' => $contact->full_name,
            'title' => $contact->title,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'notes' => $contact->notes,
            'is_default' => (bool) $contact->is_default,
            'status' => $contact->status ?? 'active',
            'status_label' => $statuses[$contact->status ?? 'active'] ?? ($contact->status ?? 'active'),
        ];
    }
}
