<?php

namespace App\Actions;

use App\Models\Production;
use App\Models\Produk;
use App\Models\SatuanConversion;
use Illuminate\Support\Facades\DB;

class CompleteProduction
{
    /**
     * Handle the completion of a Production Run.
     * Deduct stock for ingredients and increase stock for the finished product.
     * Also recalculates the HPP of the finished product based on the run cost.
     */
    public function handle(Production $production): void
    {
        DB::transaction(function () use ($production) {
            // 1. Deduct Stock for Ingredients (Production Items)
            foreach ($production->items as $item) {
                $ingredientProduk = Produk::find($item->produk_id);

                // Calculate stock deduction (convert used unit to base unit if necessary)
                $qtyToDeduct = $item->actual_qty;
                if ($ingredientProduk->satuan_id !== $item->satuan_id) {
                    $ratio = app(\App\Services\SatuanService::class)->getConversionRatio($ingredientProduk->satuan_id, $item->satuan_id);
                    $qtyToDeduct = $item->actual_qty / ($ratio ?: 1);
                }

                // There is no explicit stock column on the `produks` table in this app structure based on usual Warung schema,
                // but if we were managing stock via transactions, we'd record the usage here.
                // Record Stock Movement for Ingredient Usage
                app(\App\Actions\RecordStockMovement::class)->handle([
                    'produk_id' => $item->produk_id,
                    'satuan_id' => $item->satuan_id,
                    'type' => 'out',
                    'jumlah' => $item->actual_qty,
                    'reference_type' => 'production_usage',
                    'reference_id' => $production->id,
                    'keterangan' => "Production usage for SKU: {$production->sku}",
                ]);
            }

            // 2. Add Stock for Finished Product
            app(\App\Actions\RecordStockMovement::class)->handle([
                'produk_id' => $production->produk_id,
                'satuan_id' => $production->produk->satuan_id,
                'type' => 'in',
                'jumlah' => $production->actual_yield,
                'reference_type' => 'production_yield',
                'reference_id' => $production->id,
                'keterangan' => "Production yield for SKU: {$production->sku}",
            ]);

            // 3. Update the HPP of the finished product based on actual yield
            $finishedProduk = Produk::with('currentPrice')->find($production->produk_id);
            if ($production->actual_yield > 0) {
                $newHpp = $production->total_cost / $production->actual_yield;

                if ($finishedProduk->currentPrice) {
                    $finishedProduk->currentPrice->update([
                        'purchase_price' => $newHpp,
                    ]);
                } else {
                    $finishedProduk->prices()->create([
                        'satuan_id' => $finishedProduk->satuan_id,
                        'purchase_price' => $newHpp,
                        'retail_price' => 0,
                        'is_current' => true,
                    ]);
                }
            }
        });
    }
}
