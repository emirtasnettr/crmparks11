<?php

namespace App\Modules\Business\Requests;

use App\Modules\Business\Data\BusinessContactFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessContactRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'title' => ['required', Rule::in(BusinessContactFormData::titles())],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(array_keys(BusinessContactFormData::statuses()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_id.required' => 'İşletme seçilmelidir.',
            'full_name.required' => 'Ad soyad zorunludur.',
            'title.required' => 'Görev seçilmelidir.',
            'phone.required' => 'Telefon zorunludur.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
        ]);
    }
}
