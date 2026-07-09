<?php

namespace App\Modules\Courier\Requests;

use App\Modules\Courier\Data\CourierVehicleFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourierVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('courier.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
            'vehicle_type' => ['required', Rule::in(array_keys(CourierVehicleFormData::vehicleTypes()))],
            'plate' => ['nullable', 'string', 'max:20'],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'model_year' => ['nullable', 'integer', 'min:1990', 'max:2030'],
            'color' => ['nullable', 'string', 'max:40'],
            'license_number' => ['nullable', 'string', 'max:80'],
            'insurance_policy_number' => ['nullable', 'string', 'max:80'],
            'insurance_expiry_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(array_keys(CourierVehicleFormData::statuses()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
