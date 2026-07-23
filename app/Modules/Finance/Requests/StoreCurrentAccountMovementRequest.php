<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\CurrentAccountFormData;
use App\Modules\Finance\Models\CurrentAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'type' => ['required', 'string'],
            'document_no' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $accountId = (int) $this->input('current_account_id');
            $account = CurrentAccount::query()->find($accountId);

            if ($account === null) {
                return;
            }

            $allowed = $account->account_type === 'courier'
                ? CurrentAccountFormData::courierTransactionTypes()
                : CurrentAccountFormData::businessTransactionTypes();

            $type = (string) $this->input('type');

            if (! array_key_exists($type, $allowed)) {
                $validator->errors()->add(
                    'type',
                    'Bu cari tipi için seçilen işlem türü geçersiz.',
                );
            }
        });
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
