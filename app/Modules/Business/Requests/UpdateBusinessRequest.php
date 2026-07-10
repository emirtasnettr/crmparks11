<?php

namespace App\Modules\Business\Requests;

use App\Modules\Business\Data\BusinessFormData;
use App\Modules\Business\Models\Business;
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
        $businessId = (int) $this->route('id');

        return [
            'company_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_office' => ['nullable', 'string', 'max:255'],
            'tax_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(Business::class, 'tax_number')->ignore($businessId),
            ],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'pricing_model' => ['required', Rule::in(array_keys(BusinessFormData::pricingModels()))],
            'customer_price' => ['nullable', 'numeric', 'min:0'],
            'courier_price' => ['nullable', 'numeric', 'min:0'],
            'earning_period' => BusinessFeatures::earningsEnabled()
                ? ['required', Rule::in(array_keys(BusinessFormData::earningPeriods()))]
                : ['nullable', Rule::in(array_keys(BusinessFormData::earningPeriods()))],
            'planned_courier_count' => ['required', 'integer', 'min:1', 'max:9999'],
            'status' => ['required', Rule::in(array_keys(BusinessFormData::statuses()))],
            'contract_end_date' => [
                Rule::requiredIf(fn () => $this->input('status') === 'inactive'),
                'nullable',
                'date',
            ],
            'estimated_opening_date' => [
                Rule::requiredIf(fn () => in_array($this->input('status'), ['pending', 'contract_stage'], true)),
                'nullable',
                'date',
            ],
            'start_date' => [
                Rule::requiredIf(fn () => $this->input('status') === 'opening_stage'),
                'nullable',
                'date',
            ],
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
            'brand_name.required' => 'Marka adı zorunludur.',
            'phone.required' => 'Telefon numarası zorunludur.',
            'pricing_model.required' => 'Çalışma modeli seçilmelidir.',
            'earning_period.required' => 'Hakediş periyodu seçilmelidir.',
            'planned_courier_count.required' => 'Planlanan kurye sayısı zorunludur.',
            'planned_courier_count.integer' => 'Planlanan kurye sayısı sayı olmalıdır.',
            'planned_courier_count.min' => 'Planlanan kurye sayısı en az 1 olmalıdır.',
            'contract_end_date.required' => 'Pasif durum için sözleşme bitiş tarihi zorunludur.',
            'estimated_opening_date.required' => 'Tahmini açılış tarihi zorunludur.',
            'start_date.required' => 'Açılış aşaması için başlangıç tarihi zorunludur.',
            'logo.image' => 'Logo geçerli bir görsel dosyası olmalıdır.',
            'logo.max' => 'Logo dosyası en fazla 2 MB olabilir.',
        ];
    }
}
