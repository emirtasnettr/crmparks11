<?php

namespace App\Modules\ShiftPlanning\Requests;

use App\Modules\ShiftPlanning\Data\ShiftPlanningFormData;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftJokerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shift_planning.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var BusinessShift|null $shift */
        $shift = BusinessShift::query()->find((int) $this->route('id'));

        return [
            'work_date' => ['required', 'date'],
            'absent_courier_id' => ['required', 'integer', 'exists:couriers,id'],
            'joker_courier_id' => ['required', 'integer', 'exists:couriers,id', 'different:absent_courier_id'],
            'reason' => ['required', 'string', Rule::in(array_keys(ShiftPlanningFormData::jokerReasons()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'work_date.required' => 'İzin / hastalık tarihi zorunludur.',
            'absent_courier_id.required' => 'İzinli kurye seçilmelidir.',
            'joker_courier_id.required' => 'Joker personel seçilmelidir.',
            'joker_courier_id.different' => 'Joker personel, izinli kurye ile aynı olamaz.',
            'reason.required' => 'Sebep seçilmelidir.',
        ];
    }
}
