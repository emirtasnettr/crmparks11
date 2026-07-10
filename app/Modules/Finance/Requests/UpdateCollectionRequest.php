<?php

namespace App\Modules\Finance\Requests;

class UpdateCollectionRequest extends StoreCollectionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dashboard.financial') ?? false;
    }
}
