<?php

namespace App\Observers;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\SatuanService;

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
        $produk = $movement->produk;
        $baseSatuanId = $produk->satuan_id;

        // Calculate the amount in base unit
        $amountInBaseUnit = (float) $movement->jumlah;

        if ($movement->satuan_id !== $baseSatuanId) {
            $ratio = app(SatuanService::class)->getConversionRatio($baseSatuanId, $movement->satuan_id, $produk->id);
            if ($ratio != 0) {
                $amountInBaseUnit /= $ratio;
            }
        }

        // Sprint 3.2: Pessimistic Locking
        // Use lockForUpdate to ensure serialized access to the stock row
        $stock = Stock::where('produk_id', $produk->id)->lockForUpdate()->first();

        if (! $stock) {
            $stock = Stock::create([
                'produk_id' => $produk->id,
                'balance' => 0,
                'last_satuan_id' => $baseSatuanId,
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
        $isStorno = str_contains(strtolower($movement->keterangan ?? ''), 'storno');
        $isAdjustment = in_array($movement->reference_type, ['stock_opname', 'adjustment']) || $isStorno;

        if ($produk->track_stock && ! $isAdjustment && $change < 0 && ($stock->balance + $change) < 0) {
            throw new \RuntimeException("Stok {$produk->nama} tidak mencukupi untuk transaksi ini (Sisa: {$stock->balance}, Diminta: ".abs($change).')');
        }

        $stock->increment('balance', $change);
        $stock->update([
            'last_satuan_id' => $movement->satuan_id,
            'last_movement_id' => $event === 'created' ? $movement->id : $stock->last_movement_id,
        ]);
    }
}
