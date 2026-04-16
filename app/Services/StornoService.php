<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Actions\RecordStockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StornoService
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement
    ) {}

    /**
     * Perform storno (reversal) for a given model.
     *
     * @param Model $model The transaction model to be stornoed
     * @param string|null $reason Reason for the storno
     * @return bool
     * @throws \Exception
     */
    public function perform(Model $model, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($model, $reason) {
            if ($model instanceof StockOpname) {
                return $this->stornoStockOpname($model, $reason);
            }

            // Future modules can be added here (Purchasing, Sales, etc.)
            
            throw new \Exception("Model " . get_class($model) . " does not support storno yet.");
        });
    }

    /**
     * Specific logic for stornoing a Stock Opname.
     */
    protected function stornoStockOpname(StockOpname $opname, ?string $reason): bool
    {
        if ($opname->status !== 'completed') {
            throw new \Exception("Hanya Stock Opname dengan status 'completed' yang dapat di-storno.");
        }

        // 1. Reverse Stock Movements
        $this->reverseStockMovements($opname, $reason);

        // 2. Update Model Status and Audit information
        $opname->update([
            'status' => 'storno',
            'storno_at' => now(),
            'storno_reason' => $reason,
        ]);

        Log::info("Storno performed for StockOpname #{$opname->id}");

        return true;
    }

    /**
     * Generic logic to reverse all stock movements associated with a model.
     */
    protected function reverseStockMovements(Model $model, ?string $reason): void
    {
        $movements = StockMovement::where('reference_type', 'stock_opname') // or use a mapping if needed
            ->where('reference_id', $model->id)
            ->get();

        foreach ($movements as $movement) {
            // Create a counter-movement (In -> Out, Out -> In)
            $this->recordStockMovement->handle([
                'produk_id' => $movement->produk_id,
                'satuan_id' => $movement->satuan_id,
                'type' => $movement->type === 'in' ? 'out' : 'in',
                'jumlah' => $movement->jumlah,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'keterangan' => "STORNO: " . ($reason ?: "Pembatalan transaksi #{$model->id}"),
            ]);
        }
    }
}
