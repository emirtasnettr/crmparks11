<?php

namespace App\Modules\ShiftPlanning\Requests;

use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftDayCourier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $businessId = $shift?->business_id;
        $workDate = $this->input('work_date');

        $existing = ($shift && $workDate)
            ? BusinessShiftDayCourier::query()
                ->where('business_shift_id', $shift->id)
                ->whereDate('work_date', $workDate)
                ->pluck('courier_id')
                ->all()
            : [];

        $allowedCourierIds = $businessId
            ? BusinessCourierAssignment::query()
                ->where('business_id', $businessId)
                ->currentlyActive()
                ->pluck('courier_id')
                ->merge($existing)
                ->unique()
                ->all()
            : [];

        return [
            'work_date' => ['required', 'date'],
            'courier_ids' => ['nullable', 'array'],
            'courier_ids.*' => ['integer', Rule::in($allowedCourierIds)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'work_date.required' => 'Çalışma tarihi zorunludur.',
            'courier_ids.*.in' => 'Seçilen kuryeler bu işletmeye atanmış olmalıdır.',
        ];
    }
}
