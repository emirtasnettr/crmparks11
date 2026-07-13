<?php

namespace App\Modules\Business\Requests;

use App\Modules\Business\Data\BusinessAssignmentFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assignment.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(array_keys(BusinessAssignmentFormData::statuses()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_id.required' => 'İşletme seçilmelidir.',
            'courier_id.required' => 'Kurye seçilmelidir.',
            'start_date.required' => 'Başlangıç tarihi zorunludur.',
            'end_date.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.',
        ];
    }
}
