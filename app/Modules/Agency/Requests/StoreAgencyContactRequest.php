<?php

namespace App\Modules\Agency\Requests;

use App\Modules\Agency\Data\AgencyContactFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgencyContactRequest extends FormRequest
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
            'full_name' => ['required_without_all:first_name,last_name', 'nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'title' => ['required', Rule::in(AgencyContactFormData::titles())],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(array_keys(AgencyContactFormData::statuses()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'agency_id.required' => 'Acente seçilmelidir.',
            'full_name.required_without_all' => 'Ad soyad zorunludur.',
            'title.required' => 'Görev seçilmelidir.',
            'phone.required' => 'Telefon zorunludur.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $fullName = trim((string) $this->input('full_name'));

        if ($fullName === '') {
            $fullName = trim(trim((string) $this->input('first_name')).' '.trim((string) $this->input('last_name')));
        }

        $this->merge([
            'full_name' => $fullName,
            'is_default' => $this->boolean('is_default'),
        ]);
    }
}
