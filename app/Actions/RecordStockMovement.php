<?php

namespace App\Actions;

use App\Models\StockMovement;

class RecordStockMovement
{
    /**
     * Create a new stock movement record.
     * The summary balance in `stocks` table will be updated by StockMovementObserver.
     */
    public function handle(array $data): StockMovement
    {
        return StockMovement::create([
            'produk_id' => $data['produk_id'],
            'satuan_id' => $data['satuan_id'],
            'type' => $data['type'], // 'in' or 'out'
            'jumlah' => $data['jumlah'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
        ]);
    }
}
