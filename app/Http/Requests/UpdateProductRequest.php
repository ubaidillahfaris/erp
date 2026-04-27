<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku,'.$this->product->id],
            'barcode' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'type' => ['required', 'string', 'in:raw_material,intermediate_good,finished_good'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'unit_id' => ['required', 'exists:units,id'],
            'track_stock' => ['nullable', 'boolean'],
            'overhead_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
