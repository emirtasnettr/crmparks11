<?php

namespace App\Modules\Agency\Requests;

use App\Modules\Agency\Data\AgencyDocumentFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgencyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('agency.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'agency_id' => ['required', 'integer', 'exists:agencies,id'],
            'document_type' => ['required', Rule::in(array_keys(AgencyDocumentFormData::documentTypes()))],
            'file' => ['required', 'file', 'max:25600'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
