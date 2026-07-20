<?php

namespace App\Modules\ShiftPlanning\Requests;

use App\Modules\ShiftPlanning\Models\BusinessShift;
use Illuminate\Foundation\Http\FormRequest;

class AssignShiftCouriersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shift_planning.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'courier_ids' => array_values(array_filter(
                (array) $this->input('courier_ids', []),
                fn ($id) => $id !== null && $id !== '',
            )),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var BusinessShift|null $shift */
        $shift = BusinessShift::query()->find((int) $this->route('id'));
        $headcount = max(1, (int) ($shift?->required_headcount ?? 1));

        return [
            'courier_ids' => ['nullable', 'array', 'max:'.$headcount],
            'courier_ids.*' => ['integer', 'exists:couriers,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'courier_ids.max' => 'Atanan kurye sayısı vardiya kişi sayısını aşamaz.',
            'courier_ids.*.exists' => 'Seçilen kuryeler geçerli olmalıdır.',
        ];
    }
}
