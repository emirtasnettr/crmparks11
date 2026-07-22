<?php

namespace App\Modules\Business\Requests;

use App\Modules\Business\Models\BusinessCommercialContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessCommercialContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $businessAmount = $this->input('business_amount');
        $courierAmount = $this->input('courier_amount');

        if (is_numeric($businessAmount) && is_numeric($courierAmount)) {
            $this->merge([
                'net_profit' => round((float) $businessAmount - (float) $courierAmount, 2),
            ]);
        }

        // Paket başı modelinde garanti paket ücreti kullanılmaz.
        $this->merge(['guaranteed_hourly_package_fee' => null]);

        if ($this->input('work_type') !== BusinessCommercialContract::WORK_PER_PACKAGE
            || $this->input('guaranteed_package_count') === ''
            || $this->input('guaranteed_package_count') === null) {
            $this->merge(['guaranteed_package_count' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'work_type' => ['required', Rule::in(['per_package', 'hourly'])],
            'business_amount' => ['required', 'numeric', 'min:0'],
            'courier_amount' => ['required', 'numeric', 'min:0'],
            'net_profit' => ['nullable', 'numeric'],
            'guaranteed_hourly_package_fee' => ['nullable', 'numeric', 'min:0'],
            'guaranteed_package_count' => ['nullable', 'numeric', 'min:0.01', 'max:100000'],
            'payment_period' => ['required', Rule::in(['weekly', 'biweekly', 'monthly'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Başlangıç tarihi zorunludur.',
            'work_type.required' => 'Çalışma şekli seçilmelidir.',
            'business_amount.required' => 'İşletmeden alınan tutar zorunludur.',
            'courier_amount.required' => 'Kuryeye verilen tutar zorunludur.',
            'payment_period.required' => 'Ödeme periyodu seçilmelidir.',
        ];
    }
}
