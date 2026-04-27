<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\UnitService;
use Illuminate\Support\Facades\DB;

class RecordStockMovement
{
    /**
     * Create a new stock movement record.
     * The summary balance in `stocks` table will be updated by StockMovementObserver.
     */
    public function handle(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $product = Product::findOrFail($data['product_id']);
            $warehouseId = $data['warehouse_id'] ?? null;

            if (! $warehouseId) {
                $warehouseId = Warehouse::where('is_default', true)->value('id');
            }

            if (! $product->is_batch_tracked) {
                return $this->createMovement($data, $warehouseId);
            }

            // Batch Tracked Product Logic
            if ($data['type'] === 'in') {
                return $this->handleInboundBatch($data, $warehouseId, $product);
            } else {
                return $this->handleOutboundBatch($data, $warehouseId, $product);
            }
        });
    }

    private function createMovement(array $data, int $warehouseId, ?int $batchId = null): StockMovement
    {
        return StockMovement::create([
            'product_id' => $data['product_id'],
            'warehouse_id' => $warehouseId,
            'unit_id' => $data['unit_id'],
            'type' => $data['type'],
            'quantity' => $data['quantity'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'condition' => $data['condition'] ?? 'good',
            'stock_batch_id' => $batchId,
        ]);
    }

    private function handleInboundBatch(array $data, int $warehouseId, Product $product): StockMovement
    {
        $batchNumber = $data['batch_number'] ?? 'BATCH-'.now()->format('YmdHis');
        
        $batch = StockBatch::updateOrCreate(
            [
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'batch_number' => $batchNumber,
            ],
            [
                'unit_id' => $product->unit_id, // Batches always stored in base unit internally?
                'lot_number' => $data['lot_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'received_at' => now(),
                'source_type' => $data['reference_type'] ?? null,
                'source_id' => $data['reference_id'] ?? null,
            ]
        );

        return $this->createMovement($data, $warehouseId, $batch->id);
    }

    private function handleOutboundBatch(array $data, int $warehouseId, Product $product): StockMovement
    {
        // If specific batch is requested
        if (!empty($data['stock_batch_id'])) {
            return $this->createMovement($data, $warehouseId, $data['stock_batch_id']);
        }

        // FEFO Logic
        $requestedQty = (float) $data['quantity'];
        $baseUnitId = $product->unit_id;
        $qtyInBaseUnit = $requestedQty;

        // Convert requested qty to base unit if necessary
        if ($data['unit_id'] !== $baseUnitId) {
            $ratio = app(UnitService::class)->getConversionRatio($baseUnitId, $data['unit_id'], $product->id);
            if ($ratio != 0) {
                $qtyInBaseUnit /= $ratio;
            }
        }

        $batches = StockBatch::where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity_on_hand', '>', 0)
            ->fefo()
            ->lockForUpdate()
            ->get();

        $remainingQty = $qtyInBaseUnit;
        $lastMovement = null;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) break;

            $deductQty = min($batch->quantity_on_hand, $remainingQty);
            
            // Convert back to original unit for the movement record if possible,
            // but usually we split movements and record them in the unit they were requested.
            // If we split, we should maintain the ratio.
            $ratio = 1;
            if ($data['unit_id'] !== $baseUnitId) {
                $ratio = app(UnitService::class)->getConversionRatio($baseUnitId, $data['unit_id'], $product->id);
            }
            
            $movementQty = $deductQty * $ratio;

            $lastMovement = $this->createMovement(array_merge($data, ['quantity' => $movementQty]), $warehouseId, $batch->id);

            $remainingQty -= $deductQty;
        }

        if ($remainingQty > 0) {
            // If still remaining, either throw error or record a negative movement on a "default" or "new" batch?
            // Existing logic throws error in StockMovementObserver if track_stock is on.
            // For batch tracking, we might want to throw error early.
            if ($product->track_stock) {
                throw new \RuntimeException("Stok {$product->name} tidak mencukupi di batch mana pun untuk memenuhi permintaan {$requestedQty}.");
            }
            
            // If not tracking stock strictly, record the rest on a dummy batch or no batch?
            // Usually batch tracking implies stock tracking.
            $lastMovement = $this->createMovement(array_merge($data, ['quantity' => $remainingQty * $ratio]), $warehouseId);
        }

        return $lastMovement ?? $this->createMovement($data, $warehouseId);
    }
}
