<?php

namespace App\Modules\Business\Requests;

class StoreBusinessRequest extends UpdateBusinessRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('business.create') ?? false;
    }
}
