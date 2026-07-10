<?php

namespace App\Modules\User\Requests;

class UpdateRoleRequest extends StoreRoleRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user.update') ?? false;
    }
}
