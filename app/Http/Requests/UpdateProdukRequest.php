<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProdukRequest extends FormRequest
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
            'sku' => ['required', 'string', 'max:255', 'unique:produks,sku,'.$this->produk->id],
            'barcode' => ['nullable', 'string', 'max:255'],
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'stok_minimal' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'type' => ['required', 'string', 'in:raw_material,intermediate_good,finished_good'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'satuan_id' => ['required', 'exists:satuans,id'],
            'track_stock' => ['nullable', 'boolean'],
            'overhead_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
