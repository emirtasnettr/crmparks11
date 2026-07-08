<?php

namespace App\Modules\Business\Requests;

use App\Modules\Business\Data\BusinessContactFormData;
use Illuminate\Validation\Rule;

class UpdateBusinessContactRequest extends StoreBusinessContactRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
        ]);
    }
}
