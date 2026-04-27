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
            'product_id' => $data['product_id'],
            'unit_id' => $data['unit_id'],
            'type' => $data['type'], // 'in' or 'out'
            'quantity' => $data['quantity'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
