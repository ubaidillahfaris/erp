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
                    'date' => $restock->date,
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
                        'date' => $purchase->date,
                        'type' => 'purchase',
                        'model' => $purchase,
                    ];
                });

            // Productions (Completed only)
            $productions = Production::with(['items.product', 'product'])->where('status', 'completed')
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
                        $this->recordInbound($item->product_id, $item->unit_id, $item->quantity, 'restock', $model->id, "Legacy Restock ref: {$model->id}");
                    }
                } elseif ($type === 'purchase') {
                    foreach ($model->items as $item) {
                        $this->recordInbound($item->product_id, $item->unit_id, $item->quantity, 'purchase', $model->id, "Purchase ref: {$model->id}");
                    }
                } elseif ($type === 'production') {
                    // Ingredient Usage (OUT)
                    foreach ($model->items as $pItem) {
                        if ($pItem->actual_qty > 0) {
                            app(RecordStockMovement::class)->handle([
                                'product_id' => $pItem->product_id,
                                'unit_id' => $pItem->unit_id,
                                'type' => 'out',
                                'quantity' => $pItem->actual_qty,
                                'reference_type' => 'production_usage',
                                'reference_id' => $model->id,
                                'notes' => "Initial sync production usage SKU: {$model->sku}",
                            ]);
                        }
                    }

                    // Yield Produced (IN)
                    if ($model->actual_yield > 0) {
                        $this->recordInbound($model->product_id, $model->product->unit_id, $model->actual_yield, 'production_yield', $model->id, "Initial sync production yield SKU: {$model->sku}");
                    }
                }
            }
        });
    }

    protected function recordInbound($productId, $unitId, $quantity, $refType, $refId, $notes): void
    {
        app(RecordStockMovement::class)->handle([
            'product_id' => $productId,
            'unit_id' => $unitId,
            'type' => 'in',
            'quantity' => $quantity,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'notes' => $notes,
        ]);
    }
}
