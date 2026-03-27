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
            'tanggal' => ['required', 'date'],
            'bom_id' => ['required', 'exists:boms,id'],
            'produk_id' => ['required', 'exists:produks,id'],
            'target_yield' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['required', 'exists:produks,id'],
            'items.*.satuan_id' => ['required', 'exists:satuans,id'],
            'items.*.planned_qty' => ['required', 'numeric', 'min:0'],
        ];
    }
}
