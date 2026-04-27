<?php

namespace App\Actions;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Price;
use App\Models\Product;
use App\Services\UnitService;
use Illuminate\Support\Facades\DB;

class RecalculateHpp
{
    protected static array $visited = [];

    /**
     * Recalculate HPP for a product.
     * If it's a raw material, it uses the current purchase price.
     * If it's semi-finished or finished, it calculates from its BOM.
     */
    public function handle(Product $product, bool $isCascade = false): float
    {
        if (! $isCascade) {
            static::$visited = [];
        }

        if (in_array($product->id, static::$visited)) {
            return $this->getHppForProduct($product);
        }

        static::$visited[] = $product->id;

        // 1. If Raw Material, fetch price and trigger cascade
        if ($product->type === 'raw_material') {
            $hpp = $this->getHppForProduct($product);
            $this->cascadeUpdate($product);

            return $hpp;
        }

        // 2. If Finished or Semi-Finished, calculate from BOM
        $bom = $product->bom()->where('is_active', true)->first();
        if (! $bom) {
            return 0;
        }

        $totalHpp = 0;

        foreach ($bom->items as $item) {
            $ingredient = $item->product;

            // Fetch the price from the ingredient's currentPrice
            $ingredientHppPerUnit = $this->getHppForProduct($ingredient);

            // Unit Conversion Ratio
            $fromUnitId = $ingredient->unit_id ?? ($ingredient->currentPrice->unit_id ?? null);
            $ratio = app(UnitService::class)->getConversionRatio($fromUnitId, $item->unit_id);

            $itemCost = ($ingredientHppPerUnit / ($ratio ?: 1)) * (float) $item->quantity;

            \Log::info("HPP Trace | Product: {$product->name} | Ingredient: {$ingredient->name} | IngPrice: {$ingredientHppPerUnit} | Ratio: {$ratio} | Qty: {$item->quantity} | Cost: {$itemCost}");

            $totalHpp += $itemCost;
        }

        \Log::info("HPP Total | Product: {$product->name} | Total: {$totalHpp}");

        // 3. Update current price for this product
        $yield = (float) ($bom->expected_yield ?: 1);
        $finalCostPerUnit = $totalHpp / $yield;
        $this->updateProductHppPrice($product, $finalCostPerUnit);

        // 4. Cascade: Trigger calculation for products that use THIS as an ingredient
        $this->cascadeUpdate($product);

        return $finalCostPerUnit;
    }

    protected function getHppForProduct(Product $product): float
    {
        $price = $product->currentPrice;

        return (float) ($price ? $price->purchase_price : 0);
    }

    protected function updateProductHppPrice(Product $product, float $totalHpp): void
    {
        DB::transaction(function () use ($product, $totalHpp) {
            // Capture existing prices BEFORE deactivating the current record
            // Accessing currentPrice property will lazy load it if not already loaded
            $currentPrice = $product->currentPrice;
            $retailPrice = (float) ($currentPrice->retail_price ?? 0);
            $wholesalePrice = (float) ($currentPrice->wholesale_price ?? 0);

            // Deactivate existing current price in the database
            $product->prices()->where('is_current', true)->update(['is_current' => false]);

            // Create new current price record with the updated HPP (purchase_price)
            // while preserving existing retail and wholesale prices
            $product->prices()->create([
                'unit_id' => $product->unit_id,
                'purchase_price' => $totalHpp,
                'retail_price' => $retailPrice,
                'wholesale_price' => $wholesalePrice,
                'is_current' => true,
            ]);

            // Forget relationship to ensure fresh data on next access
            $product->unsetRelation('currentPrice');
        });
    }

    protected function cascadeUpdate(Product $product): void
    {
        // Find all BOM items where this product is an ingredient
        $affectedBoms = BomItem::where('product_id', $product->id)
            ->with('bom.product')
            ->get()
            ->pluck('bom')
            ->unique('id');

        foreach ($affectedBoms as $bom) {
            if ($bom && $bom->product) {
                $this->handle($bom->product, true);
            }
        }
    }
}
