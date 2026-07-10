<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\CollectionFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkCollectRequest extends FormRequest
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
            'ids.*' => ['integer', 'exists:finance_collections,id'],
            'collection_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(array_keys(CollectionFormData::paymentMethods()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ids' => 'tahsilatlar',
            'collection_date' => 'tahsilat tarihi',
            'payment_method' => 'ödeme yöntemi',
        ];
    }
}
