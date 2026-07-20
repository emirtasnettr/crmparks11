<?php

namespace App\Modules\Business\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessCommercialContractRequest extends FormRequest
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
            'end_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
