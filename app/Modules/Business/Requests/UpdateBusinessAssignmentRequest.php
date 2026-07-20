<?php

namespace App\Modules\Business\Requests;

use App\Modules\Business\Data\BusinessAssignmentFormData;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Business\Services\BusinessAssignmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class UpdateBusinessAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assignment.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(array_keys(BusinessAssignmentFormData::statuses()))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var BusinessCourierAssignment|null $assignment */
            $assignment = BusinessCourierAssignment::query()->find((int) $this->route('id'));

            if ($assignment === null) {
                return;
            }

            $service = app(BusinessAssignmentService::class);
            $status = (string) ($this->input('status') ?: $assignment->status);
            $endDate = $this->input('end_date', $assignment->end_date?->toDateString());

            if (! $service->wouldBeCurrentlyActive($status, $endDate)) {
                return;
            }

            try {
                $service->assertCourierHasNoOtherActiveAssignment((int) $assignment->courier_id, (int) $assignment->id);
            } catch (ValidationException $e) {
                $validator->errors()->merge($e->errors());
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Başlangıç tarihi zorunludur.',
            'end_date.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.',
        ];
    }
}
