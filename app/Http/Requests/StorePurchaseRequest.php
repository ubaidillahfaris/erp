<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Default payment_method to cash if not provided
        if (! $this->payment_method) {
            $this->merge(['payment_method' => 'cash']);
        }

        if ($this->transaction_type !== 'purchase') {
            $this->merge([
                'vendor_id' => null,
                'payment_method' => 'cash',
            ]);

            if (is_array($this->items)) {
                $items = $this->items;
                foreach ($items as &$item) {
                    $item['unit_price'] = 0;
                }
                $this->merge(['items' => $items]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'no_invoice' => ['nullable', 'string', 'max:100'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'date' => ['required', 'date'],
            'transaction_type' => ['required', 'in:purchase,gift,adjustment'],
            'payment_method' => ['required', 'string', 'in:cash,transfer,credit'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:255'],
            'items.*.lot_number' => ['nullable', 'string', 'max:255'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'], // 20MB max per file
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->transaction_type === 'purchase') {
                if (empty($this->vendor_id)) {
                    $validator->errors()->add('vendor_id', 'Vendor wajib diisi untuk transaksi Pembelian.');
                }
                foreach ($this->items ?? [] as $index => $item) {
                    if (isset($item['unit_price']) && (float) $item['unit_price'] <= 0) {
                        $validator->errors()->add("items.{$index}.unit_price", 'Harga satuan harus lebih dari 0 untuk transaksi Pembelian.');
                    }
                }
            }
        });
    }
}
