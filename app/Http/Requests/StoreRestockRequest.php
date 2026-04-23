<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->total_bayar === null) {
            $this->merge([
                'total_bayar' => 0,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
            'status_pembayaran' => 'required|string|in:lunas,hutang,bayar_berkala',
            'vendor_id' => 'required_if:status_pembayaran,hutang|required_if:status_pembayaran,bayar_berkala|nullable|exists:vendors,id',
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

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vendor_id.required_if' => 'Vendor wajib diisi jika pembayaran berstatus hutang atau bayar berkala.',
        ];
    }
}
