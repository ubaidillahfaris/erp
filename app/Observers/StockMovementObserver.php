<?php

namespace App\Observers;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\UnitService;

class StockMovementObserver
{
    /**
     * Handle the StockMovement "created" event.
     */
    public function created(StockMovement $stockMovement): void
    {
        $this->updateBalance($stockMovement, 'created');
    }

    /**
     * Handle the StockMovement "deleted" event.
     */
    public function deleted(StockMovement $stockMovement): void
    {
        $this->updateBalance($stockMovement, 'deleted');
    }

    /**
     * Update the Stock summary table.
     */
    private function updateBalance(StockMovement $movement, string $event): void
    {
        $product = $movement->product;
        $baseUnitId = $product->unit_id;

        // Calculate the amount in base unit
        $amountInBaseUnit = (float) $movement->quantity;

        if ($movement->unit_id !== $baseUnitId) {
            $ratio = app(UnitService::class)->getConversionRatio($baseUnitId, $movement->unit_id, $product->id);
            if ($ratio != 0) {
                $amountInBaseUnit /= $ratio;
            }
        }

        // Sprint 3.2: Pessimistic Locking
        // Use lockForUpdate to ensure serialized access to the stock row
        $stock = Stock::where('product_id', $product->id)
            ->where('warehouse_id', $movement->warehouse_id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            $stock = Stock::create([
                'product_id' => $product->id,
                'warehouse_id' => $movement->warehouse_id,
                'balance' => 0,
                'last_unit_id' => $baseUnitId,
            ]);
            // Refresh with lock
            $stock = Stock::where('id', $stock->id)->lockForUpdate()->first();
        }

        $change = $movement->type === 'in' ? $amountInBaseUnit : -$amountInBaseUnit;

        // If deleted, we reverse the change
        if ($event === 'deleted') {
            $change = -$change;
        }

        // Validate stock sufficiency for outgoing movements
        // Sprint 3.2: Only enforce if track_stock is enabled and NOT a stock_opname/storno
        $isStorno = str_contains(strtolower($movement->notes ?? ''), 'storno');
        $isAdjustment = in_array($movement->reference_type, ['stock_opname', 'adjustment']) || $isStorno;

        if ($product->track_stock && ! $isAdjustment && $change < 0 && ($stock->balance + $change) < 0) {
            $warehouseName = $movement->warehouse->name ?? 'Gudang';
            throw new \RuntimeException("Stok {$product->name} di {$warehouseName} tidak mencukupi untuk transaksi ini (Sisa: {$stock->balance}, Diminta: ".abs($change).')');
        }

        $stock->increment('balance', $change);
        $stock->update([
            'last_unit_id' => $movement->unit_id,
            'last_movement_id' => $event === 'created' ? $movement->id : $stock->last_movement_id,
        ]);
    }
}
