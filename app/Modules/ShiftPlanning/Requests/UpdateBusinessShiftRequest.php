<?php

namespace App\Modules\ShiftPlanning\Requests;

use App\Modules\ShiftPlanning\Data\ShiftPlanningFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shift_planning.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'start_time' => $this->normalizeTime($this->input('start_time')),
            'end_time' => $this->normalizeTime($this->input('end_time')),
            'days_of_week' => array_values(array_filter(
                (array) $this->input('days_of_week', []),
                fn ($day) => $day !== null && $day !== '',
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
        return [
            'name' => ['required', 'string', 'max:120'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['integer', Rule::in(array_keys(ShiftPlanningFormData::weekDays()))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vardiya adı zorunludur.',
            'start_time.required' => 'Başlangıç saati zorunludur.',
            'end_time.required' => 'Bitiş saati zorunludur.',
            'start_date.required' => 'Başlangıç tarihi zorunludur.',
            'end_date.required' => 'Bitiş tarihi zorunludur.',
            'end_date.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.',
            'days_of_week.required' => 'En az bir gün seçilmelidir.',
            'days_of_week.min' => 'En az bir gün seçilmelidir.',
        ];
    }
}
