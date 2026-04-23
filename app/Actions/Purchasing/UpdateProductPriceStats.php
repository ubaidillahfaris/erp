<?php

namespace App\Actions\Purchasing;

use App\Models\ProductPriceStat;
use App\Models\Produk;
use App\Models\PurchaseItem;
use App\Services\SatuanService;
use Illuminate\Support\Facades\DB;

class UpdateProductPriceStats
{
    public function __construct(protected SatuanService $satuanService) {}

    /**
     * Recalculate price statistics for a specific product.
     */
    public function handle(int $produkId): void
    {
        $produk = Produk::findOrFail($produkId);
        $baseSatuanId = $produk->satuan_id;

        if (! $baseSatuanId) {
            return;
        }

        // Fetch all finalized purchase items for this product
        $items = PurchaseItem::where('produk_id', $produkId)
            ->whereHas('purchase', function ($query) {
                $query->where('status', 'finalized')
                    ->where('transaction_type', 'purchase');
            })
            ->get();

        if ($items->isEmpty()) {
            // If no purchases, we might want to reset the stats but keep the record
            ProductPriceStat::updateOrCreate(
                ['produk_id' => $produkId, 'satuan_id' => $baseSatuanId],
                ['avg_price' => 0, 'min_price' => 0, 'max_price' => 0, 'last_purchase_price' => 0]
            );

            return;
        }

        $normalizedPrices = [];
        $totalWeight = 0;
        $weightedSum = 0;
        $lastPurchasePrice = 0;

        foreach ($items as $item) {
            $ratio = $this->satuanService->getConversionRatio($item->satuan_id, $baseSatuanId, $produkId);

            // Normalized price per base unit
            // 1 Unit of item->satuan_id = Ratio units of baseSatuanId
            // So Price per baseSatuanId = Price per item->satuan_id / Ratio
            $normalizedPrice = (float) $item->harga_satuan / ($ratio ?: 1);

            // For weighted average, we need to convert the 'jumlah' to base unit as well
            $normalizedQty = (float) $item->jumlah * ($ratio ?: 1);

            $normalizedPrices[] = $normalizedPrice;
            $weightedSum += ($normalizedPrice * $normalizedQty);
            $totalWeight += $normalizedQty;

            // Store the very last purchase price in normalized form
            $lastPurchasePrice = $normalizedPrice;
        }

        $avgPrice = $totalWeight > 0 ? ($weightedSum / $totalWeight) : 0;
        $minPrice = min($normalizedPrices);
        $maxPrice = max($normalizedPrices);

        DB::transaction(function () use ($produkId, $baseSatuanId, $avgPrice, $minPrice, $maxPrice, $lastPurchasePrice) {
            ProductPriceStat::updateOrCreate(
                ['produk_id' => $produkId, 'satuan_id' => $baseSatuanId],
                [
                    'avg_price' => $avgPrice,
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                    'last_purchase_price' => $lastPurchasePrice,
                ]
            );
        });
    }
}
