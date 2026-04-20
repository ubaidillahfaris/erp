<?php

namespace App\Http\Requests;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
            'produk_id' => [
                'required',
                'exists:produks,id',
                Rule::unique('boms')->ignore($this->route('bom')),
            ],
            'nama' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'auto_deduct_on_sale' => 'boolean',
            'expected_yield' => 'required|numeric|min:0.0001',
            'yield_satuan_id' => 'required|exists:satuans,id',
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|exists:produks,id',
            'items.*.satuan_id' => 'nullable|exists:satuans,id',
            'items.*.jumlah' => 'required|numeric|min:0.0001',
        ];
    }
}
