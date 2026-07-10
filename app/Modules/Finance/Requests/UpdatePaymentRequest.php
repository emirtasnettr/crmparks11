<?php

namespace App\Modules\Finance\Requests;

class UpdatePaymentRequest extends StorePaymentRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dashboard.financial') ?? false;
    }
}
