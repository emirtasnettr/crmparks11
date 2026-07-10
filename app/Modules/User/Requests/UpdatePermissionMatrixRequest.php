<?php

namespace App\Modules\User\Requests;

use App\Modules\User\Data\PermissionManagementFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionMatrixRequest extends FormRequest
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
        $editableRoles = array_keys(array_diff_key(
            PermissionManagementFormData::selectableRoles(),
            ['super_admin' => true],
        ));

        return [
            'role' => [
                'required',
                'string',
                Rule::in($editableRoles),
            ],
            'permissions' => ['present', 'array'],
            'permissions.*' => [
                'string',
                Rule::in(PermissionManagementFormData::allMatrixPermissionSlugs()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'Rol seçilmelidir.',
            'role.in' => 'Seçilen rol güncellenemez.',
            'permissions.present' => 'Yetki listesi gönderilmelidir.',
        ];
    }
}
