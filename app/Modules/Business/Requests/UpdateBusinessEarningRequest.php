<?php

namespace App\Modules\Business\Requests;

class UpdateBusinessEarningRequest extends StoreBusinessEarningRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('earning.update') ?? false;
    }
}
