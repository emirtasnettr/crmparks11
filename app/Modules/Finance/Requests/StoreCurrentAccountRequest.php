<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\CurrentAccountFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurrentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->can('dashboard.financial')
            && $user->hasAnyRole(['super_admin', 'general_manager']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['business', 'courier'])],
            'title' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'cari tipi',
            'title' => 'cari ünvanı',
            'phone' => 'telefon',
            'email' => 'e-posta',
            'tax_number' => 'vergi no / TCKN',
        ];
    }
}
