<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBOMRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('boms')->ignore($this->route('bom')),
            ],
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::unique('boms')->ignore($this->route('bom')),
            ],
            'name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'auto_deduct_on_sale' => 'boolean',
            'expected_yield' => 'required|numeric|min:0.0001',
            'yield_unit_id' => 'required|exists:units,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ];
    }
}
