<?php

namespace App\Modules\User\Requests;

use App\Modules\User\Data\UserManagementFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(array_keys(UserManagementFormData::roleLabels()))],
            'status' => ['required', Rule::in(array_keys(UserManagementFormData::statuses()))],
            'linked_business_id' => ['nullable', 'integer', 'exists:businesses,id'],
            'linked_courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
            'linked_agency_id' => ['nullable', 'integer', 'exists:agencies,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'ad',
            'last_name' => 'soyad',
            'email' => 'e-posta',
            'phone' => 'telefon',
            'password' => 'şifre',
            'roles' => 'roller',
            'status' => 'durum',
            'linked_business_id' => 'bağlı işletme',
            'linked_courier_id' => 'bağlı kurye',
            'linked_agency_id' => 'bağlı acente',
        ];
    }
}
