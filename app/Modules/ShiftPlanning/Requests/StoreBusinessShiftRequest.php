<?php

namespace App\Modules\ShiftPlanning\Requests;

use App\Modules\ShiftPlanning\Data\ShiftPlanningFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shift_planning.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'start_time' => $this->normalizeTime($this->input('start_time')),
            'end_time' => $this->normalizeTime($this->input('end_time')),
            'days_of_week' => array_values(array_filter(
                (array) $this->input('days_of_week', ShiftPlanningFormData::defaultDays()),
                fn ($day) => $day !== null && $day !== '',
            )),
            'courier_ids' => array_values(array_filter(
                (array) $this->input('courier_ids', []),
                fn ($id) => $id !== null && $id !== '',
            )),
        ]);
    }

    private function normalizeTime(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return substr($value, 0, 5);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $businessId = (int) $this->input('business_id');

        $allowedCourierIds = $businessId > 0
            ? \App\Modules\Business\Models\BusinessCourierAssignment::query()
                ->where('business_id', $businessId)
                ->currentlyActive()
                ->pluck('courier_id')
                ->all()
            : [];

        return [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'name' => ['required', 'string', 'max:120'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['integer', Rule::in(array_keys(ShiftPlanningFormData::weekDays()))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
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
            'business_id.required' => 'İşletme seçilmelidir.',
            'name.required' => 'Vardiya adı zorunludur.',
            'start_time.required' => 'Başlangıç saati zorunludur.',
            'end_time.required' => 'Bitiş saati zorunludur.',
            'start_date.required' => 'Başlangıç tarihi zorunludur.',
            'end_date.required' => 'Bitiş tarihi zorunludur.',
            'end_date.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.',
            'days_of_week.required' => 'En az bir gün seçilmelidir.',
            'days_of_week.min' => 'En az bir gün seçilmelidir.',
            'courier_ids.*.in' => 'Seçilen kuryeler bu işletmeye atanmış olmalıdır.',
        ];
    }
}
