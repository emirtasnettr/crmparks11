<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\CurrentAccountFormData;
use Illuminate\Validation\Rule;

class UpdateCurrentAccountRequest extends StoreCurrentAccountRequest
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
        return array_merge(parent::rules(), [
            'status' => ['nullable', Rule::in(array_keys(CurrentAccountFormData::statuses()))],
        ]);
    }
}
