<?php

namespace App\Modules\Business\Requests;

use App\Modules\Business\Data\BusinessDocumentFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessDocumentRequest extends FormRequest
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
            'document_type' => ['required', Rule::in(array_keys(BusinessDocumentFormData::documentTypes()))],
            'file' => ['required', 'file', 'max:25600'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_id.required' => 'İşletme seçilmelidir.',
            'document_type.required' => 'Evrak türü seçilmelidir.',
            'file.required' => 'Dosya seçilmelidir.',
        ];
    }
}
