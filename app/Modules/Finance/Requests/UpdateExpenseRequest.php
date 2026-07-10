<?php

namespace App\Modules\Finance\Requests;

class UpdateExpenseRequest extends StoreExpenseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dashboard.financial') ?? false;
    }
}
