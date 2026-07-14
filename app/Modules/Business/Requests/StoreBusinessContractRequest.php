<?php

namespace App\Modules\Business\Requests;

use App\Modules\Business\Data\BusinessContractFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('business.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'contract_number' => ['nullable', 'string', 'max:100', 'unique:contracts,contract_number'],
            'contract_type' => ['required', Rule::in(array_keys(BusinessContractFormData::contractTypes()))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(array_keys(BusinessContractFormData::storedStatuses()))],
            'contract_file' => ['nullable', 'file', 'mimes:pdf', 'max:'.config('crmlog.upload.max_size')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_id.required' => 'İşletme seçilmelidir.',
            'contract_type.required' => 'Sözleşme türü seçilmelidir.',
            'start_date.required' => 'Başlangıç tarihi zorunludur.',
            'end_date.required' => 'Bitiş tarihi zorunludur.',
            'end_date.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.',
            'contract_file.max' => 'Dosya boyutu en fazla '.config('crmlog.upload.max_size_mb').' MB olabilir.',
        ];
    }
}
