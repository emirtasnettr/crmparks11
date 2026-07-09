<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\InvoiceFormData;
use App\Modules\Finance\Models\FinanceInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
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
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'earning_line_id' => [
                'nullable',
                'integer',
                'exists:earning_lines,id',
                Rule::unique('finance_invoices', 'earning_line_id'),
            ],
            'invoice_type' => ['nullable', Rule::in(array_keys(InvoiceFormData::invoiceTypes()))],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'subtotal' => ['required', 'numeric', 'gt:0'],
            'vat_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'business_id' => 'işletme',
            'earning_line_id' => 'hakediş',
            'invoice_type' => 'fatura türü',
            'invoice_date' => 'fatura tarihi',
            'due_date' => 'vade tarihi',
            'subtotal' => 'ara toplam',
            'vat_rate' => 'KDV oranı',
            'description' => 'açıklama',
        ];
    }
}
