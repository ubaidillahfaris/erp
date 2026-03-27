<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestockRequest extends FormRequest
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
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
            'status_pembayaran' => 'required|string|in:lunas,hutang,bayar_berkala',
            'total_bayar' => 'required|numeric|min:0',
            'biaya_tambahan' => 'nullable|array',
            'biaya_tambahan.*.nama' => 'required|string|max:255',
            'biaya_tambahan.*.nominal' => 'required|numeric',
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|exists:produks,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.jumlah' => 'required|numeric|min:0.0001',
            'items.*.harga_satuan' => 'required|numeric|min:0',
        ];
    }
}
