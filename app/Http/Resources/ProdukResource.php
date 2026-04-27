<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'kategori' => $this->kategori,
            'unit_id' => $this->unit_id,
            'base_unit' => $this->unit->name,
            'price' => (float) ($this->currentPrice?->retail_price ?? 0),
            'cost' => (float) ($this->currentPrice?->purchase_price ?? 0),
            'stock' => (float) ($this->stock?->balance ?? 0),
        ];
    }
}
