<?php

namespace App\Modules\Business\Requests;

use App\Modules\Business\Models\Business;
use Illuminate\Validation\Rule;

class StoreBusinessRequest extends UpdateBusinessRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('business.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'tax_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(Business::class, 'tax_number'),
            ],
        ]);
    }
}
