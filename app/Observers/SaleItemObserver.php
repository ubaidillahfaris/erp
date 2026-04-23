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
        $produk = $saleItem->produk()->with('bom.items')->first();

        // 1. Deduct Ingredients (BOM) if configured
        if ($produk->bom && $produk->bom->auto_deduct_on_sale && $produk->bom->is_active) {
            foreach ($produk->bom->items as $bomItem) {
                (new RecordStockMovement)->handle([
                    'produk_id' => $bomItem->produk_id,
                    'satuan_id' => $bomItem->satuan_id,
                    'type' => 'out',
                    'jumlah' => $bomItem->jumlah * $saleItem->qty,
                    'reference_type' => Sale::class,
                    'reference_id' => $saleItem->sale_id,
                    'keterangan' => "Auto BOM: Penjualan INV-{$saleItem->sale->invoice_number} ({$produk->nama})",
                ]);
            }
        }

        // 2. Deduct Product Stock if configured
        if ($produk->track_stock) {
            (new RecordStockMovement)->handle([
                'produk_id' => $saleItem->produk_id,
                'satuan_id' => $saleItem->satuan_id,
                'type' => 'out',
                'jumlah' => $saleItem->qty,
                'reference_type' => Sale::class,
                'reference_id' => $saleItem->sale_id,
                'keterangan' => "Penjualan INV-{$saleItem->sale->invoice_number}",
            ]);
        }

        // 2. Record COGS Journal per item
        if ($saleItem->cost > 0) {
            Journal::create([
                'tanggal' => $saleItem->sale->tanggal->format('Y-m-d'),
                'type' => 'kredit',
                'amount' => $saleItem->cost * $saleItem->qty,
                'category' => 'hpp',
                'payment_method' => 'stok',
                'description' => "HPP {$saleItem->produk->nama} INV-{$saleItem->sale->invoice_number}",
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
