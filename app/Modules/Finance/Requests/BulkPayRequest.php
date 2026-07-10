<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\PaymentFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkPayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dashboard.financial') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:finance_payments,id'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(array_keys(PaymentFormData::paymentMethods()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ids' => 'ödemeler',
            'payment_date' => 'ödeme tarihi',
            'payment_method' => 'ödeme yöntemi',
        ];
    }
}
