<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\ExpenseFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
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
            'expense_type' => ['required', Rule::in(array_keys(ExpenseFormData::expenseTypes()))],
            'courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
            'agency_id' => ['nullable', 'integer', 'exists:agencies,id'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'vat_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'payment_status' => ['nullable', Rule::in(array_keys(ExpenseFormData::paymentStatuses()))],
            'document_no' => ['nullable', 'string', 'max:50'],
            'earning_line_id' => ['nullable', 'integer', 'exists:earning_lines,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'expense_type' => 'gider türü',
            'courier_id' => 'kurye',
            'agency_id' => 'acente',
            'expense_date' => 'gider tarihi',
            'amount' => 'tutar',
            'vat_rate' => 'KDV oranı',
            'description' => 'açıklama',
            'payment_status' => 'ödeme durumu',
            'document_no' => 'belge no',
        ];
    }
}
