<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\CollectionFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectionRequest extends FormRequest
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
            'revenue_id' => ['nullable', 'integer', 'exists:finance_revenues,id'],
            'invoice_no' => ['nullable', 'string', 'max:50'],
            'due_date' => ['required', 'date'],
            'collection_date' => ['nullable', 'date'],
            'total_amount' => ['required', 'numeric', 'gt:0'],
            'collected_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(array_keys(CollectionFormData::paymentMethods()))],
            'payment_reference' => ['nullable', 'string', 'max:50'],
            'bank' => ['nullable', 'string', 'max:100'],
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
            'revenue_id' => 'gelir kaydı',
            'invoice_no' => 'fatura no',
            'due_date' => 'vade tarihi',
            'collection_date' => 'tahsilat tarihi',
            'total_amount' => 'toplam tutar',
            'collected_amount' => 'tahsil edilen tutar',
            'payment_method' => 'ödeme yöntemi',
            'payment_reference' => 'referans no',
            'bank' => 'banka',
            'description' => 'açıklama',
        ];
    }
}
