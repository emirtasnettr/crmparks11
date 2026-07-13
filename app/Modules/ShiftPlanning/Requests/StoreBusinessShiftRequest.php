<?php

namespace App\Modules\ShiftPlanning\Requests;

use App\Modules\Business\Models\BusinessCourierAssignment;
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
        $headcount = max(1, (int) $this->input('required_headcount', 1));

        $allowedCourierIds = $businessId > 0
            ? BusinessCourierAssignment::query()
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
            'required_headcount' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'courier_ids' => ['nullable', 'array', 'max:'.$headcount],
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
            'required_headcount.required' => 'Kişi sayısı zorunludur.',
            'required_headcount.min' => 'En az 1 kişi tanımlanmalıdır.',
            'courier_ids.max' => 'Atanan kurye sayısı vardiya kişi sayısını aşamaz.',
            'courier_ids.*.in' => 'Seçilen kuryeler bu işletmeye atanmış olmalıdır.',
        ];
    }
}
