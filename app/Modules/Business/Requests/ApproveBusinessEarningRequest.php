<?php

namespace App\Modules\Business\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveBusinessEarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('earning.approve') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
