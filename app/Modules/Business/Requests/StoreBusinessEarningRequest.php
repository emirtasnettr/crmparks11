<?php

namespace App\Modules\Business\Requests;

use App\Modules\Business\Data\BusinessEarningFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessEarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('earning.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
            'work_date' => ['required', 'date'],
            'pricing_model' => ['required', Rule::in(array_keys(BusinessEarningFormData::pricingModels()))],
            'package_count' => ['nullable', 'integer', 'min:0'],
            'revenue_unit_price' => ['nullable', 'numeric', 'min:0'],
            'courier_unit_price' => ['nullable', 'numeric', 'min:0'],
            'revenue_total' => ['nullable', 'numeric', 'min:0'],
            'courier_payment' => ['nullable', 'numeric', 'min:0'],
            'extra_income' => ['nullable', 'numeric', 'min:0'],
            'extra_expense' => ['nullable', 'numeric', 'min:0'],
            'deduction' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(array_keys(BusinessEarningFormData::statuses()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_id.required' => 'İşletme seçilmelidir.',
            'courier_id.required' => 'Kurye seçilmelidir.',
            'work_date.required' => 'Hakediş tarihi seçilmelidir.',
            'work_date.date' => 'Geçerli bir tarih girin.',
        ];
    }
}
