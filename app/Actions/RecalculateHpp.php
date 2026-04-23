<?php

namespace App\Actions;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Price;
use App\Models\Produk;
use App\Services\SatuanService;
use Illuminate\Support\Facades\DB;

class RecalculateHpp
{
    protected static array $visited = [];

    /**
     * Recalculate HPP for a product.
     * If it's a raw material, it uses the current purchase price.
     * If it's semi-finished or finished, it calculates from its BOM.
     */
    public function handle(Produk $produk, bool $isCascade = false): float
    {
        if (! $isCascade) {
            static::$visited = [];
        }

        if (in_array($produk->id, static::$visited)) {
            return $this->getHppForProduct($produk);
        }

        static::$visited[] = $produk->id;

        // 1. If Raw Material, fetch price and trigger cascade
        if ($produk->type === 'raw_material') {
            $hpp = $this->getHppForProduct($produk);
            $this->cascadeUpdate($produk);

            return $hpp;
        }

        // 2. If Finished or Semi-Finished, calculate from BOM
        $bom = $produk->bom()->where('is_active', true)->first();
        if (! $bom) {
            return 0;
        }

        $totalHpp = 0;

        foreach ($bom->items as $item) {
            $ingredient = $item->produk;

            // Fetch the price from the ingredient's currentPrice
            $ingredientHppPerUnit = $this->getHppForProduct($ingredient);

            // Unit Conversion Ratio
            $fromUnitId = $ingredient->satuan_id ?? ($ingredient->currentPrice->satuan_id ?? null);
            $ratio = app(SatuanService::class)->getConversionRatio($fromUnitId, $item->satuan_id);

            $itemCost = ($ingredientHppPerUnit / ($ratio ?: 1)) * (float) $item->jumlah;

            \Log::info("HPP Trace | Product: {$produk->nama} | Ingredient: {$ingredient->nama} | IngPrice: {$ingredientHppPerUnit} | Ratio: {$ratio} | Qty: {$item->jumlah} | Cost: {$itemCost}");

            $totalHpp += $itemCost;
        }

        \Log::info("HPP Total | Product: {$produk->nama} | Total: {$totalHpp}");

        // 3. Update current price for this product
        $yield = (float) ($bom->expected_yield ?: 1);
        $finalCostPerUnit = $totalHpp / $yield;
        $this->updateProductHppPrice($produk, $finalCostPerUnit);

        // 4. Cascade: Trigger calculation for products that use THIS as an ingredient
        $this->cascadeUpdate($produk);

        return $finalCostPerUnit;
    }

    protected function getHppForProduct(Produk $produk): float
    {
        $price = $produk->currentPrice;

        return (float) ($price ? $price->purchase_price : 0);
    }

    protected function updateProductHppPrice(Produk $produk, float $totalHpp): void
    {
        DB::transaction(function () use ($produk, $totalHpp) {
            // Capture existing prices BEFORE deactivating the current record
            // Accessing currentPrice property will lazy load it if not already loaded
            $currentPrice = $produk->currentPrice;
            $retailPrice = (float) ($currentPrice->retail_price ?? 0);
            $wholesalePrice = (float) ($currentPrice->wholesale_price ?? 0);

            // Deactivate existing current price in the database
            $produk->prices()->where('is_current', true)->update(['is_current' => false]);

            // Create new current price record with the updated HPP (purchase_price)
            // while preserving existing retail and wholesale prices
            $produk->prices()->create([
                'satuan_id' => $produk->satuan_id,
                'purchase_price' => $totalHpp,
                'retail_price' => $retailPrice,
                'wholesale_price' => $wholesalePrice,
                'is_current' => true,
            ]);

            // Forget relationship to ensure fresh data on next access
            $produk->unsetRelation('currentPrice');
        });
    }

    protected function cascadeUpdate(Produk $produk): void
    {
        // Find all BOM items where this product is an ingredient
        $affectedBoms = BomItem::where('produk_id', $produk->id)
            ->with('bom.produk')
            ->get()
            ->pluck('bom')
            ->unique('id');

        foreach ($affectedBoms as $bom) {
            if ($bom && $bom->produk) {
                $this->handle($bom->produk, true);
            }
        }
    }
}
