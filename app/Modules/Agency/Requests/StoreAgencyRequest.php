<?php

namespace App\Modules\Agency\Requests;

use App\Modules\Agency\Models\Agency;
use Illuminate\Validation\Rule;

class StoreAgencyRequest extends UpdateAgencyRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('agency.create') ?? false;
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
                Rule::unique(Agency::class, 'tax_number'),
            ],
        ]);
    }
}
