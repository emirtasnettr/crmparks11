<?php

namespace App\Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'earning_line_id' => ['nullable', 'integer', 'exists:earning_lines,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'direction.required' => 'İşlem yönü seçilmelidir.',
            'amount.required' => 'Tutar zorunludur.',
            'amount.gt' => 'Tutar 0’dan büyük olmalıdır.',
            'reason.required' => 'Neden zorunludur.',
            'reason.min' => 'Neden en az 5 karakter olmalıdır.',
        ];
    }
}
