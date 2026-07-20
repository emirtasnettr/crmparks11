<?php

namespace App\Modules\Courier\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourierPasswordRequest extends FormRequest
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
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'password' => 'şifre',
            'password_confirmation' => 'şifre tekrarı',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Yeni şifre zorunludur.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre tekrarı eşleşmiyor.',
        ];
    }
}
