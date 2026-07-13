<?php

namespace App\Modules\ShiftPlanning\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyBusinessShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shift_planning.delete') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
