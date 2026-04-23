<?php

namespace App\Actions;

use App\Models\Production;
use App\Models\Purchase;
use App\Models\Restock;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class SyncProductStock
{
    /**
     * Rebuild the entire stock history from scratch based on Restocks,
     * Purchases, and Productions.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            // 1. Clear existing history (PostgreSQL compatible)
            DB::statement('TRUNCATE TABLE stock_movements, stocks RESTART IDENTITY CASCADE;');

            // 2. Fetch all sources and sort by date for accurate Stock Card
            // Legacy Restocks
            $restocks = Restock::with('items')->get()->map(function ($restock) {
                return [
                    'date' => $restock->tanggal,
                    'type' => 'restock',
                    'model' => $restock,
                ];
            });

            // Primary Purchases (Finalized only)
            $purchases = Purchase::where('status', 'finalized')
                ->with('items')
                ->get()
                ->map(function ($purchase) {
                    return [
                        'date' => $purchase->tanggal,
                        'type' => 'purchase',
                        'model' => $purchase,
                    ];
                });

            // Productions (Completed only)
            $productions = Production::with(['items.produk', 'produk'])->where('status', 'completed')
                ->get()
                ->map(function ($production) {
                    return [
                        'date' => $production->created_at, // Use created_at as fallback for production date
                        'type' => 'production',
                        'model' => $production,
                    ];
                });

            // Merge and Sort chronologically
            $merged = $restocks->concat($purchases)->concat($productions)
                ->sortBy('date');

            // 3. Process merged entries
            foreach ($merged as $entry) {
                $type = $entry['type'];
                $model = $entry['model'];

                if ($type === 'restock') {
                    foreach ($model->items as $item) {
                        $this->recordInbound($item->produk_id, $item->satuan_id, $item->jumlah, 'restock', $model->id, "Legacy Restock ref: {$model->id}");
                    }
                } elseif ($type === 'purchase') {
                    foreach ($model->items as $item) {
                        $this->recordInbound($item->produk_id, $item->satuan_id, $item->jumlah, 'purchase', $model->id, "Purchase ref: {$model->id}");
                    }
                } elseif ($type === 'production') {
                    // Ingredient Usage (OUT)
                    foreach ($model->items as $pItem) {
                        if ($pItem->actual_qty > 0) {
                            app(RecordStockMovement::class)->handle([
                                'produk_id' => $pItem->produk_id,
                                'satuan_id' => $pItem->satuan_id,
                                'type' => 'out',
                                'jumlah' => $pItem->actual_qty,
                                'reference_type' => 'production_usage',
                                'reference_id' => $model->id,
                                'keterangan' => "Initial sync production usage SKU: {$model->sku}",
                            ]);
                        }
                    }

                    // Yield Produced (IN)
                    if ($model->actual_yield > 0) {
                        $this->recordInbound($model->produk_id, $model->produk->satuan_id, $model->actual_yield, 'production_yield', $model->id, "Initial sync production yield SKU: {$model->sku}");
                    }
                }
            }
        });
    }

    protected function recordInbound($produkId, $satuanId, $jumlah, $refType, $refId, $keterangan): void
    {
        app(RecordStockMovement::class)->handle([
            'produk_id' => $produkId,
            'satuan_id' => $satuanId,
            'type' => 'in',
            'jumlah' => $jumlah,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'keterangan' => $keterangan,
        ]);
    }
}
