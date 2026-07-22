<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\CurrentAccountFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurrentAccountMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->can('dashboard.financial')
            && $user->hasAnyRole(['super_admin', 'general_manager']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_account_id' => ['required', 'integer', 'exists:current_accounts,id'],
            'transaction_date' => ['required', 'date'],
            'type' => ['required', Rule::in(array_keys(CurrentAccountFormData::transactionTypes()))],
            'document_no' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_account_id' => 'cari',
            'transaction_date' => 'işlem tarihi',
            'type' => 'işlem türü',
            'document_no' => 'belge no',
            'amount' => 'tutar',
            'description' => 'açıklama',
        ];
    }
}
