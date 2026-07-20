<?php

namespace App\Modules\User\Requests;

use App\Modules\User\Data\UserManagementFormData;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends StoreUserRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = (int) $this->route('id');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(array_keys(UserManagementFormData::assignableRoleLabels()))],
            'status' => ['required', Rule::in(array_keys(UserManagementFormData::statuses()))],
            'linked_business_id' => ['nullable', 'integer', 'exists:businesses,id'],
            'linked_agency_id' => ['nullable', 'integer', 'exists:agencies,id'],
        ];
    }
}
