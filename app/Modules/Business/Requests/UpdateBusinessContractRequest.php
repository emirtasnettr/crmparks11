<?php

namespace App\Modules\Business\Requests;

use Illuminate\Validation\Rule;

class UpdateBusinessContractRequest extends StoreBusinessContractRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $contractId = (int) $this->route('id');

        return array_merge(parent::rules(), [
            'contract_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('contracts', 'contract_number')->ignore($contractId),
            ],
        ]);
    }
}
