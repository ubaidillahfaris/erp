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
        if (!$this->payment_method) {
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
                    $item['harga_satuan'] = 0;
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
            'tanggal' => ['required', 'date'],
            'transaction_type' => ['required', 'in:purchase,gift,adjustment'],
            'payment_method' => ['required', 'string', 'in:cash,transfer,credit'],
            'keterangan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['required', 'exists:produks,id'],
            'items.*.satuan_id' => ['required', 'exists:satuans,id'],
            'items.*.jumlah' => ['required', 'numeric', 'min:0.0001'],
            'items.*.harga_satuan' => ['required', 'numeric', 'min:0'],
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
                    if (isset($item['harga_satuan']) && (float) $item['harga_satuan'] <= 0) {
                        $validator->errors()->add("items.{$index}.harga_satuan", 'Harga satuan harus lebih dari 0 untuk transaksi Pembelian.');
                    }
                }
            }
        });
    }
}
