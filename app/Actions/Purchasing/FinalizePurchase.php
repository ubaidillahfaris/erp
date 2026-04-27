<?php

namespace App\Actions\Purchasing;

use App\Actions\RecalculateHpp;
use App\Actions\RecordStockMovement;
use App\Models\Journal;
use App\Models\Price;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class FinalizePurchase
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected RecalculateHpp $recalculateHpp,
        protected UpdateProductPriceStats $updateProductPriceStats
    ) {}

    /**
     * Finalize a purchase transaction.
     * This handles stock updates, price history, and statistics.
     */
    public function handle(Purchase $purchase, ?array $signatureMetadata = null): void
    {
        if ($purchase->status === 'finalized') {
            throw new \Exception('Transaksi ini sudah difinalisasi.');
        }

        DB::transaction(function () use ($purchase, $signatureMetadata) {
            // 1. Update status and audit log
            $purchase->update([
                'status' => 'finalized',
                'signature_log' => $signatureMetadata,
            ]);

            foreach ($purchase->items as $item) {
                // 2. Update Product Price (Current Active Purchase Price)
                // Only for "purchase" type transactions
                if ($purchase->transaction_type === 'purchase') {
                    $this->updateCurrentPrice($item->product_id, $item->unit_id, (float) $item->unit_price);
                }

                // 3. Record Stock Movement
                $this->recordStockMovement->handle([
                    'product_id' => $item->product_id,
                    'unit_id' => $item->unit_id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'notes' => "Purchase ref: {$purchase->id} ({$purchase->transaction_type})",
                    'batch_number' => $item->batch_number,
                    'lot_number' => $item->lot_number,
                    'expiry_date' => $item->expiry_date,
                ]);

                // 4. Update Price Aggregates (AVG/MIN/MAX)
                $this->updateProductPriceStats->handle($item->product_id);

                // 5. Recalculate HPP (if it's a purchase)
                if ($purchase->transaction_type === 'purchase') {
                    $this->recalculateHpp->handle($item->product);
                }
            }

            // 6. Record Financial Journal (only for purchase type)
            if ($purchase->transaction_type === 'purchase' && (float) $purchase->total_biaya > 0) {
                Journal::create([
                    'date' => $purchase->date->format('Y-m-d'),
                    'type' => 'kredit',
                    'amount' => $purchase->total_biaya,
                    'category' => 'persediaan',
                    'payment_method' => 'hutang',
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'description' => 'Pembelian (Auto-Journal): '.($purchase->notes ?? "Inbound #{$purchase->id}"),
                ]);
            }
        });
    }

    /**
     * Update the current active purchase price for a product.
     */
    protected function updateCurrentPrice(int $productId, int $unitId, float $purchasePrice): void
    {
        $currentPrice = Price::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('is_current', true)
            ->first();

        // If price is different or doesn't exist, create a new history record
        if (! $currentPrice || (float) $currentPrice->purchase_price !== (float) $purchasePrice) {
            if ($currentPrice) {
                $currentPrice->update(['is_current' => false]);
            }

            Price::create([
                'product_id' => $productId,
                'unit_id' => $unitId,
                'purchase_price' => $purchasePrice,
                'retail_price' => $currentPrice ? $currentPrice->retail_price : 0,
                'wholesale_price' => $currentPrice ? $currentPrice->wholesale_price : null,
                'is_current' => true,
            ]);
        }
    }
}
