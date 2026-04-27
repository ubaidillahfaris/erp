<?php

namespace App\Actions\Purchasing;

use App\Models\Product;
use App\Models\ProductPriceStat;
use App\Models\PurchaseItem;
use App\Services\UnitService;
use Illuminate\Support\Facades\DB;

class UpdateProductPriceStats
{
    public function __construct(protected UnitService $unitService) {}

    /**
     * Recalculate price statistics for a specific product.
     */
    public function handle(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $baseUnitId = $product->unit_id;

        if (! $baseUnitId) {
            return;
        }

        // Fetch all finalized purchase items for this product
        $items = PurchaseItem::where('product_id', $productId)
            ->whereHas('purchase', function ($query) {
                $query->where('status', 'finalized')
                    ->where('transaction_type', 'purchase');
            })
            ->get();

        if ($items->isEmpty()) {
            // If no purchases, we might want to reset the stats but keep the record
            ProductPriceStat::updateOrCreate(
                ['product_id' => $productId, 'unit_id' => $baseUnitId],
                ['avg_price' => 0, 'min_price' => 0, 'max_price' => 0, 'last_purchase_price' => 0]
            );

            return;
        }

        $normalizedPrices = [];
        $totalWeight = 0;
        $weightedSum = 0;
        $lastPurchasePrice = 0;

        foreach ($items as $item) {
            $ratio = $this->unitService->getConversionRatio($item->unit_id, $baseUnitId, $productId);

            // Normalized price per base unit
            // 1 Unit of item->unit_id = Ratio units of baseUnitId
            // So Price per baseUnitId = Price per item->unit_id / Ratio
            $normalizedPrice = (float) $item->unit_price / ($ratio ?: 1);

            // For weighted average, we need to convert the 'quantity' to base unit as well
            $normalizedQty = (float) $item->quantity * ($ratio ?: 1);

            $normalizedPrices[] = $normalizedPrice;
            $weightedSum += ($normalizedPrice * $normalizedQty);
            $totalWeight += $normalizedQty;

            // Store the very last purchase price in normalized form
            $lastPurchasePrice = $normalizedPrice;
        }

        $avgPrice = $totalWeight > 0 ? ($weightedSum / $totalWeight) : 0;
        $minPrice = min($normalizedPrices);
        $maxPrice = max($normalizedPrices);

        DB::transaction(function () use ($productId, $baseUnitId, $avgPrice, $minPrice, $maxPrice, $lastPurchasePrice) {
            ProductPriceStat::updateOrCreate(
                ['product_id' => $productId, 'unit_id' => $baseUnitId],
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
