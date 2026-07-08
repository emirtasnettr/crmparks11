<?php

namespace App\Modules\Courier\Requests;

use App\Modules\Courier\Data\CourierFormData;
use App\Modules\Courier\Models\Courier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourierRequest extends FormRequest
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
        $courierId = (int) $this->route('id');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'tc_number' => [
                'nullable',
                'string',
                'max:11',
                Rule::unique(Courier::class, 'tc_number')->ignore($courierId),
            ],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'courier_type' => ['required', Rule::in(array_keys(CourierFormData::courierTypes()))],
            'agency_id' => ['nullable', 'string'],
            'tax_office' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'vehicle_type' => ['required', Rule::in(array_keys(CourierFormData::vehicleTypes()))],
            'plate' => ['nullable', 'string', 'max:20'],
            'vehicle_brand' => ['nullable', 'string', 'max:100'],
            'vehicle_model' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'iban' => ['nullable', 'string', 'max:50'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(CourierFormData::statuses()))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'profile_photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Ad zorunludur.',
            'last_name.required' => 'Soyad zorunludur.',
            'phone.required' => 'Telefon zorunludur.',
            'courier_type.required' => 'Kurye tipi seçilmelidir.',
            'vehicle_type.required' => 'Araç tipi seçilmelidir.',
            'start_date.required' => 'Başlangıç tarihi zorunludur.',
            'profile_photo.image' => 'Profil fotoğrafı geçerli bir görsel dosyası olmalıdır.',
            'profile_photo.max' => 'Profil fotoğrafı en fazla 2 MB olabilir.',
        ];
    }
}
