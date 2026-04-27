<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', 'unique:productions'],
            'date' => ['required', 'date'],
            'bom_id' => ['required', 'exists:boms,id'],
            'product_id' => ['required', 'exists:products,id'],
            'target_yield' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.planned_qty' => ['required', 'numeric', 'min:0'],
        ];
    }
}
