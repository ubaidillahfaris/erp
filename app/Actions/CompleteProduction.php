<?php

namespace App\Actions;

use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Exceptions\MissingOverheadRateException;
use App\Models\Account;
use App\Models\Production;
use App\Models\Produk;
use App\Services\JournalService;
use Illuminate\Support\Facades\DB;

class CompleteProduction
{
    public function __construct(
        protected JournalService $journalService
    ) {
    }

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
            
            // Hard Constraint: Overhead Rate Validation
            if (($finishedProduk->overhead_rate_per_unit ?? 0) <= 0) {
                throw new MissingOverheadRateException($finishedProduk->nama);
            }

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

            // 4. Record Financial Journal (Double-Entry)
            $materialCostCents = (int) round((float) ($production->total_cost ?? 0) * 100);
            $overheadAppliedCents = (int) round((float) $finishedProduk->overhead_rate_per_unit * $production->actual_yield);

            $journalData = new JournalEntryData(
                items: [
                    // Debit 1302: Persediaan Barang Jadi = material_cost + overhead_applied
                    new JournalItemData(
                        account_id: Account::findByCode('1302')->id,
                        amount: $materialCostCents + $overheadAppliedCents,
                        type: 'debit'
                    ),
                    // Credit 1301: Persediaan Bahan Baku = material_cost
                    new JournalItemData(
                        account_id: Account::findByCode('1301')->id,
                        amount: $materialCostCents,
                        type: 'credit'
                    ),
                    // Credit 5102: Biaya Overhead = overhead_applied
                    new JournalItemData(
                        account_id: Account::findByCode('5102')->id,
                        amount: $overheadAppliedCents,
                        type: 'credit'
                    ),
                ],
                tanggal: $production->tanggal,
                description: "Produksi selesai: {$production->sku}",
                journalable: $production
            );

            $this->journalService->record($journalData);
        });
    }
}
