<?php

namespace App\Modules\Finance\Requests;

use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends StoreInvoiceRequest
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
        $rules = parent::rules();
        $rules['earning_line_id'] = [
            'nullable',
            'integer',
            'exists:earning_lines,id',
            Rule::unique('finance_invoices', 'earning_line_id')->ignore($this->route('id')),
        ];

        return $rules;
    }
}
