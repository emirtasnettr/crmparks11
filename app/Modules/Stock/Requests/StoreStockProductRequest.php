<?php

namespace App\Modules\Stock\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:stock_products,sku'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'in:adet,çift,takım,koli'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
