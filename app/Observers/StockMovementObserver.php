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

            \Log::info("StockMovement Observer | Product: {$produk->id} | Used: {$movement->satuan_id} | Base: {$baseSatuanId} | Ratio: {$ratio} | AmountBase: {$amountInBaseUnit}");
        }

        $stock = Stock::firstOrCreate(
            ['produk_id' => $produk->id],
            ['last_satuan_id' => $baseSatuanId, 'balance' => 0]
        );

        $change = $movement->type === 'in' ? $amountInBaseUnit : -$amountInBaseUnit;

        // If deleted, we reverse the change
        if ($event === 'deleted') {
            $change = -$change;
        }

        $stock->increment('balance', $change);
        $stock->update([
            'last_satuan_id' => $movement->satuan_id,
            'last_movement_id' => $event === 'created' ? $movement->id : $stock->last_movement_id,
        ]);
    }
}
