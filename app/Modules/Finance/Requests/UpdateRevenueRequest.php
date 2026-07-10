<?php

namespace App\Modules\Finance\Requests;

class UpdateRevenueRequest extends StoreRevenueRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dashboard.financial') ?? false;
    }
}
