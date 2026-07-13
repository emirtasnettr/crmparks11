<?php

namespace App\Modules\Stock\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'assigned_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
