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
            'date' => 'required|date',
            'notes' => 'nullable|string|max:255',
            'status_pembayaran' => 'required|string|in:lunas,hutang,bayar_berkala',
            'vendor_id' => 'required_if:status_pembayaran,hutang|required_if:status_pembayaran,bayar_berkala|nullable|exists:vendors,id',
            'total_bayar' => 'required|numeric|min:0',
            'biaya_tambahan' => 'nullable|array',
            'biaya_tambahan.*.name' => 'required|string|max:255',
            'biaya_tambahan.*.nominal' => 'required|numeric',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:255',
            'items.*.lot_number' => 'nullable|string|max:255',
            'items.*.expiry_date' => 'nullable|date',
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
