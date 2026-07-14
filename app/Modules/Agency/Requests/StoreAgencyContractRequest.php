<?php

namespace App\Modules\Agency\Requests;

use App\Modules\Agency\Data\AgencyContractFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgencyContractRequest extends FormRequest
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
            'contract_number' => ['nullable', 'string', 'max:100', 'unique:contracts,contract_number'],
            'contract_type' => ['required', Rule::in(array_keys(AgencyContractFormData::contractTypes()))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'auto_renewal' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['draft', 'active'])],
            'contract_file' => ['nullable', 'file', 'mimes:pdf', 'max:'.config('crmlog.upload.max_size')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contract_file.max' => 'Dosya boyutu en fazla '.config('crmlog.upload.max_size_mb').' MB olabilir.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'auto_renewal' => $this->boolean('auto_renewal'),
        ]);
    }
}
