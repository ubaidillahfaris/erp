<?php

namespace App\Observers;

use App\Actions\RecordStockMovement;
use App\Models\Journal;
use App\Models\Sale;
use App\Models\SaleItem;

class SaleItemObserver
{
    public function created(SaleItem $saleItem): void
    {
        $product = $saleItem->product()->with('bom.items')->first();

        // 1. Deduct Ingredients (BOM) if configured
        if ($product->bom && $product->bom->auto_deduct_on_sale && $product->bom->is_active) {
            foreach ($product->bom->items as $bomItem) {
                (new RecordStockMovement)->handle([
                    'product_id' => $bomItem->product_id,
                    'unit_id' => $bomItem->unit_id,
                    'type' => 'out',
                    'quantity' => $bomItem->quantity * $saleItem->qty,
                    'reference_type' => Sale::class,
                    'reference_id' => $saleItem->sale_id,
                    'notes' => "Auto BOM: Penjualan INV-{$saleItem->sale->invoice_number} ({$product->name})",
                ]);
            }
        }

        // 2. Deduct Product Stock if configured
        if ($product->track_stock) {
            (new RecordStockMovement)->handle([
                'product_id' => $saleItem->product_id,
                'unit_id' => $saleItem->unit_id,
                'type' => 'out',
                'quantity' => $saleItem->qty,
                'reference_type' => Sale::class,
                'reference_id' => $saleItem->sale_id,
                'notes' => "Penjualan INV-{$saleItem->sale->invoice_number}",
            ]);
        }

        // 2. Record COGS Journal per item
        if ($saleItem->cost > 0) {
            Journal::create([
                'date' => $saleItem->sale->date->format('Y-m-d'),
                'type' => 'kredit',
                'amount' => $saleItem->cost * $saleItem->qty,
                'category' => 'hpp',
                'payment_method' => 'stok',
                'description' => "HPP {$saleItem->product->name} INV-{$saleItem->sale->invoice_number}",
                'reference_type' => Sale::class,
                'reference_id' => $saleItem->sale_id,
            ]);
        }
    }

    /**
     * Handle the SaleItem "updated" event.
     */
    public function updated(SaleItem $saleItem): void
    {
        //
    }

    /**
     * Handle the SaleItem "deleted" event.
     */
    public function deleted(SaleItem $saleItem): void
    {
        //
    }

    /**
     * Handle the SaleItem "restored" event.
     */
    public function restored(SaleItem $saleItem): void
    {
        //
    }

    /**
     * Handle the SaleItem "force deleted" event.
     */
    public function forceDeleted(SaleItem $saleItem): void
    {
        //
    }
}
