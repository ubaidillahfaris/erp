<?php

namespace App\Actions\Purchasing;

use App\Models\Purchase;
use App\Models\Price;
use App\Actions\RecordStockMovement;
use App\Actions\RecalculateHpp;
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
                    $this->updateCurrentPrice($item->produk_id, $item->satuan_id, (float) $item->harga_satuan);
                }

                // 3. Record Stock Movement
                $this->recordStockMovement->handle([
                    'produk_id' => $item->produk_id,
                    'satuan_id' => $item->satuan_id,
                    'type' => 'in',
                    'jumlah' => $item->jumlah,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'keterangan' => "Purchase ref: {$purchase->id} ({$purchase->transaction_type})",
                ]);

                // 4. Update Price Aggregates (AVG/MIN/MAX)
                $this->updateProductPriceStats->handle($item->produk_id);

                // 5. Recalculate HPP (if it's a purchase)
                if ($purchase->transaction_type === 'purchase') {
                    $this->recalculateHpp->handle($item->produk);
                }
            }

            // 6. Record Financial Journal (only for purchase type)
            if ($purchase->transaction_type === 'purchase' && (float) $purchase->total_biaya > 0) {
                \App\Models\Journal::create([
                    'tanggal' => $purchase->tanggal->format('Y-m-d'),
                    'type' => 'kredit',
                    'amount' => $purchase->total_biaya,
                    'category' => 'persediaan',
                    'payment_method' => 'hutang',
                    'reference_type' => \App\Models\Purchase::class,
                    'reference_id' => $purchase->id,
                    'description' => "Pembelian (Auto-Journal): " . ($purchase->keterangan ?? "Inbound #{$purchase->id}"),
                ]);
            }
        });
    }

    /**
     * Mirror existing logic from RestockController to maintain consistency.
     */
    protected function updateCurrentPrice(int $produkId, int $satuanId, float $purchasePrice): void
    {
        $currentPrice = Price::where('produk_id', $produkId)
            ->where('satuan_id', $satuanId)
            ->where('is_current', true)
            ->first();

        // If price is different or doesn't exist, create a new history record
        if (!$currentPrice || (float) $currentPrice->purchase_price !== (float) $purchasePrice) {
            if ($currentPrice) {
                $currentPrice->update(['is_current' => false]);
            }

            Price::create([
                'produk_id' => $produkId,
                'satuan_id' => $satuanId,
                'purchase_price' => $purchasePrice,
                'retail_price' => $currentPrice ? $currentPrice->retail_price : 0,
                'wholesale_price' => $currentPrice ? $currentPrice->wholesale_price : null,
                'is_current' => true,
            ]);
        }
    }
}
