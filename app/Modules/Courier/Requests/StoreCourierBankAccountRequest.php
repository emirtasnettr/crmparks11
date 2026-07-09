<?php

namespace App\Modules\Courier\Requests;

use App\Modules\Courier\Data\CourierBankAccountFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourierBankAccountRequest extends FormRequest
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
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
            'bank_key' => ['required', Rule::in(array_keys(CourierBankAccountFormData::banks()))],
            'account_holder' => ['required', 'string', 'max:120'],
            'iban' => ['required', 'string', 'regex:/^TR\d{24}$/'],
            'branch_code' => ['nullable', 'string', 'max:20'],
            'account_number' => ['nullable', 'string', 'max:30'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(array_keys(CourierBankAccountFormData::statuses()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('iban')) {
            $this->merge([
                'iban' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('iban')) ?? ''),
            ]);
        }
    }
}
