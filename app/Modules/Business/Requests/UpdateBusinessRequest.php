<?php

namespace App\Modules\Business\Requests;

use App\Modules\Business\Data\BusinessFormData;
use App\Modules\Business\Support\BusinessFeatures;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_office' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'pricing_model' => ['required', Rule::in(array_keys(BusinessFormData::pricingModels()))],
            'customer_price' => ['nullable', 'numeric', 'min:0'],
            'courier_price' => ['nullable', 'numeric', 'min:0'],
            'earning_period' => BusinessFeatures::earningsEnabled()
                ? ['required', Rule::in(array_keys(BusinessFormData::earningPeriods()))]
                : ['nullable', Rule::in(array_keys(BusinessFormData::earningPeriods()))],
            'status' => ['required', Rule::in(array_keys(BusinessFormData::statuses()))],
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
            'pricing_model.required' => 'Çalışma modeli seçilmelidir.',
            'earning_period.required' => 'Hakediş periyodu seçilmelidir.',
            'logo.image' => 'Logo geçerli bir görsel dosyası olmalıdır.',
            'logo.max' => 'Logo dosyası en fazla 2 MB olabilir.',
        ];
    }
}
