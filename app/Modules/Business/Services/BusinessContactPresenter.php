<?php

namespace App\Modules\Business\Services;

use App\Modules\Business\Models\BusinessContact;

class BusinessContactPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(BusinessContact $contact): array
    {
        $contact->loadMissing('business');

        return [
            'id' => $contact->id,
            'business_id' => $contact->business_id,
            'business_name' => $contact->business?->company_name ?? '—',
            'full_name' => $contact->full_name,
            'title' => $contact->title,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'is_default' => (bool) $contact->is_default,
            'status' => $contact->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(BusinessContact $contact): array
    {
        return [
            'id' => $contact->id,
            'business_id' => $contact->business_id,
            'full_name' => $contact->full_name,
            'title' => $contact->title,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'is_default' => (bool) $contact->is_default,
            'status' => $contact->status,
        ];
    }
}
