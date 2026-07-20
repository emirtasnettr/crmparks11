<?php

namespace App\Modules\ShiftPlanning\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shift_planning.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');

        if (! is_string($startDate) || $startDate === '') {
            $startDate = now()->toDateString();
        }

        if (! is_string($endDate) || $endDate === '') {
            $endDate = now()->addMonth()->toDateString();
        }

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'start_time' => $this->normalizeTime($this->input('start_time')),
            'end_time' => $this->normalizeTime($this->input('end_time')),
            'start_date' => $startDate,
            'end_date' => $endDate,
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
        $headcount = max(1, (int) $this->input('required_headcount', 1));

        return [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'name' => ['required', 'string', 'max:120'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'required_headcount' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
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
            'business_id.required' => 'İşletme seçilmelidir.',
            'name.required' => 'Vardiya adı zorunludur.',
            'start_time.required' => 'Başlangıç saati zorunludur.',
            'end_time.required' => 'Bitiş saati zorunludur.',
            'start_date.required' => 'Başlangıç tarihi zorunludur.',
            'end_date.required' => 'Bitiş tarihi zorunludur.',
            'end_date.after_or_equal' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.',
            'required_headcount.required' => 'Kişi sayısı zorunludur.',
            'required_headcount.min' => 'En az 1 kişi tanımlanmalıdır.',
            'courier_ids.max' => 'Atanan kurye sayısı vardiya kişi sayısını aşamaz.',
            'courier_ids.*.exists' => 'Seçilen kuryeler geçerli olmalıdır.',
        ];
    }
}
