<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProdukRequest extends FormRequest
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
            'sku' => ['nullable', 'string', 'max:255', 'unique:produks,sku'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'nama' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'deskripsi' => ['nullable', 'string'],
            'stok_minimal' => ['required', 'integer', 'min:0'],
            'type' => ['required', 'string', 'in:raw_material,intermediate_good,finished_good'],
            'satuan_id' => ['required', 'exists:satuans,id'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'track_stock' => ['nullable', 'boolean'],
            'overhead_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
