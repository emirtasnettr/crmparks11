<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\RevenueFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRevenueRequest extends FormRequest
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
            'revenue_type' => ['required', Rule::in(array_keys(RevenueFormData::revenueTypes()))],
            'period_label' => ['nullable', 'string', 'max:100'],
            'invoice_no' => ['nullable', 'string', 'max:50'],
            'revenue_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'vat_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'collection_status' => ['nullable', Rule::in(array_keys(RevenueFormData::collectionStatuses()))],
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
            'business_id' => 'işletme',
            'revenue_type' => 'gelir türü',
            'period_label' => 'hakediş dönemi',
            'invoice_no' => 'fatura no',
            'revenue_date' => 'gelir tarihi',
            'amount' => 'tutar',
            'vat_rate' => 'KDV oranı',
            'description' => 'açıklama',
            'collection_status' => 'tahsil durumu',
        ];
    }
}
