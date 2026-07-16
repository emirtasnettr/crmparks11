<?php

namespace App\Modules\Agency\Requests;

use App\Modules\Agency\Data\AgencyFormData;
use App\Modules\Agency\Models\Agency;
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
        $agencyId = (int) $this->route('id');

        return [
            'company_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'tax_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique(Agency::class, 'tax_number')->ignore($agencyId),
            ],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
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
            'brand_name.required' => 'Marka adı zorunludur.',
            'phone.required' => 'Telefon numarası zorunludur.',
            'tax_number.required' => 'Vergi numarası zorunludur.',
            'city.required' => 'İl seçilmelidir.',
            'district.required' => 'İlçe seçilmelidir.',
            'logo.image' => 'Logo geçerli bir görsel dosyası olmalıdır.',
            'logo.max' => 'Logo dosyası en fazla 2 MB olabilir.',
        ];
    }
}
