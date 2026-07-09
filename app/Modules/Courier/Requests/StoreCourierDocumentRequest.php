<?php

namespace App\Modules\Courier\Requests;

use App\Modules\Courier\Data\CourierDocumentFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourierDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('courier.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
            'document_type' => ['required', Rule::in(array_keys(CourierDocumentFormData::documentTypes()))],
            'document_number' => ['nullable', 'string', 'max:120'],
            'file' => ['required', 'file', 'max:25600'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
