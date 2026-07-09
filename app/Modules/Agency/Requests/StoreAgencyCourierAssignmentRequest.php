<?php

namespace App\Modules\Agency\Requests;

use App\Modules\Agency\Data\AgencyCourierFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgencyCourierAssignmentRequest extends FormRequest
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
            'courier_id' => [
                'required',
                'integer',
                Rule::exists('couriers', 'id')->where(fn ($query) => $query->whereNull('agency_id')),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(array_keys(AgencyCourierFormData::statuses()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'agency_id.required' => 'Acente seçilmelidir.',
            'courier_id.required' => 'Kurye seçilmelidir.',
            'courier_id.exists' => 'Seçilen kurye başka bir acenteye bağlı veya bulunamadı.',
            'start_date.required' => 'Başlangıç tarihi zorunludur.',
            'end_date.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.',
        ];
    }
}
