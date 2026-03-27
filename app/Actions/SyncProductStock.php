<?php

namespace App\Actions;

use App\Models\Produk;
use App\Models\Restock;
use App\Models\Production;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class SyncProductStock
{
    /**
     * Rebuild the entire stock history from scratch based on Restocks and Productions.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            // 1. Clear existing history (PostgreSQL compatible)
            DB::statement('TRUNCATE TABLE stock_movements, stocks RESTART IDENTITY CASCADE;');

            // 2. Sync from Restocks
            Restock::with('items')->chunk(100, function ($restocks) {
                foreach ($restocks as $restock) {
                    foreach ($restock->items as $item) {
                        app(RecordStockMovement::class)->handle([
                            'produk_id' => $item->produk_id,
                            'satuan_id' => $item->satuan_id,
                            'type' => 'in',
                            'jumlah' => $item->jumlah,
                            'reference_type' => 'restock',
                            'reference_id' => $restock->id,
                            'keterangan' => "Initial sync from Restock ref: {$restock->id}",
                        ]);
                    }
                }
            });

            // 3. Sync from Productions
            Production::with('items.produk')->where('status', 'completed')->chunk(100, function ($productions) {
                foreach ($productions as $production) {
                    // Ingredient Usage
                    foreach ($production->items as $pItem) {
                        if ($pItem->actual_qty > 0) {
                            app(RecordStockMovement::class)->handle([
                                'produk_id' => $pItem->produk_id,
                                'satuan_id' => $pItem->satuan_id,
                                'type' => 'out',
                                'jumlah' => $pItem->actual_qty,
                                'reference_type' => 'production_usage',
                                'reference_id' => $production->id,
                                'keterangan' => "Initial sync production usage SKU: {$production->sku}",
                            ]);
                        }
                    }

                    // Yield Produced
                    if ($production->actual_yield > 0) {
                        app(RecordStockMovement::class)->handle([
                            'produk_id' => $production->produk_id,
                            'satuan_id' => $production->produk->satuan_id,
                            'type' => 'in',
                            'jumlah' => $production->actual_yield,
                            'reference_type' => 'production_yield',
                            'reference_id' => $production->id,
                            'keterangan' => "Initial sync production yield SKU: {$production->sku}",
                        ]);
                    }
                }
            });
        });
    }
}
