<?php

namespace App\Modules\Agency\Requests;

use App\Modules\Agency\Data\AgencyFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgencyRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_office' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['required', 'string', 'max:50'],
            'mersis_number' => ['nullable', 'string', 'max:50'],
            'trade_registry_number' => ['nullable', 'string', 'max:50'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:1000'],
            'commission_rate' => ['nullable', 'string', 'max:20'],
            'payment_period' => ['nullable', Rule::in(array_keys(AgencyFormData::paymentPeriods()))],
            'bank_key' => ['nullable', Rule::in(array_keys(AgencyFormData::banks()))],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(array_keys(AgencyFormData::statuses()))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Firma ünvanı zorunludur.',
            'phone.required' => 'Telefon numarası zorunludur.',
            'tax_number.required' => 'Vergi numarası zorunludur.',
            'city.required' => 'İl seçilmelidir.',
            'district.required' => 'İlçe seçilmelidir.',
            'address.required' => 'Açık adres zorunludur.',
            'logo.image' => 'Logo geçerli bir görsel dosyası olmalıdır.',
            'logo.max' => 'Logo dosyası en fazla 2 MB olabilir.',
        ];
    }
}
