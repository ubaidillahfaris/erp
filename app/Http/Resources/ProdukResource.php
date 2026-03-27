<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdukResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'kategori' => $this->kategori,
            'satuan_id' => $this->satuan_id,
            'base_unit' => $this->satuan->nama,
            'price' => (float) ($this->currentPrice?->retail_price ?? 0),
            'cost' => (float) ($this->currentPrice?->purchase_price ?? 0),
            'stock' => (float) ($this->stock?->balance ?? 0),
        ];
    }
}
