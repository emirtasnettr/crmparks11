<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\InvoiceFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkInvoiceRequest extends FormRequest
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
            'earning_ids' => ['required', 'array', 'min:1'],
            'earning_ids.*' => ['integer', 'exists:earning_lines,id'],
            'invoice_type' => ['required', Rule::in(array_keys(InvoiceFormData::invoiceTypes()))],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'vat_rate' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'earning_ids' => 'hakedişler',
            'invoice_type' => 'fatura türü',
            'invoice_date' => 'fatura tarihi',
            'due_date' => 'vade tarihi',
            'vat_rate' => 'KDV oranı',
        ];
    }
}
