<?php

namespace App\Modules\ShiftPlanning\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return [
            'scope' => ['required', Rule::in(['day', 'all'])],
            'work_date' => ['required_if:scope,day', 'nullable', 'date'],
            'week' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scope.required' => 'Silme kapsamı seçilmelidir.',
            'scope.in' => 'Geçersiz silme kapsamı.',
            'work_date.required_if' => 'Gün silmek için tarih gereklidir.',
        ];
    }
}
