<?php

namespace App\Modules\Courier\Requests;

use App\Modules\Courier\Models\Courier;
use Illuminate\Validation\Rule;

class StoreCourierRequest extends UpdateCourierRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('courier.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'tc_number' => [
                'nullable',
                'string',
                'max:11',
                Rule::unique(Courier::class, 'tc_number'),
            ],
        ]);
    }
}
