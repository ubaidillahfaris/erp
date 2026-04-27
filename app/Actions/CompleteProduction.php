<?php

namespace App\Actions;

use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Exceptions\MissingOverheadRateException;
use App\Models\Account;
use App\Models\Product;
use App\Models\Production;
use App\Services\JournalService;
use App\Services\UnitService;
use Illuminate\Support\Facades\DB;

class CompleteProduction
{
    public function __construct(
        protected JournalService $journalService
    ) {}

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
                $ingredientProduct = Product::find($item->product_id);

                // Calculate stock deduction (convert used unit to base unit if necessary)
                $qtyToDeduct = $item->actual_qty;
                if ($ingredientProduct->unit_id !== $item->unit_id) {
                    $ratio = app(UnitService::class)->getConversionRatio($ingredientProduct->unit_id, $item->unit_id);
                    $qtyToDeduct = $item->actual_qty / ($ratio ?: 1);
                }

                // Record Stock Movement for Ingredient Usage
                app(RecordStockMovement::class)->handle([
                    'product_id' => $item->product_id,
                    'unit_id' => $item->unit_id,
                    'type' => 'out',
                    'quantity' => $item->actual_qty,
                    'reference_type' => 'production_usage',
                    'reference_id' => $production->id,
                    'notes' => "Production usage for SKU: {$production->sku}",
                ]);
            }

            // 2. Add Stock for Finished Product
            app(RecordStockMovement::class)->handle([
                'product_id' => $production->product_id,
                'unit_id' => $production->product->unit_id,
                'type' => 'in',
                'quantity' => $production->actual_yield,
                'reference_type' => 'production_yield',
                'reference_id' => $production->id,
                'notes' => "Production yield for SKU: {$production->sku}",
            ]);

            // 3. Update the HPP of the finished product based on actual yield
            $finishedProduct = Product::with('currentPrice')->find($production->product_id);

            // Hard Constraint: Overhead Rate Validation
            if (($finishedProduct->overhead_rate_per_unit ?? 0) <= 0) {
                throw new MissingOverheadRateException($finishedProduct->name);
            }

            if ($production->actual_yield > 0) {
                $newHpp = $production->total_cost / $production->actual_yield;

                if ($finishedProduct->currentPrice) {
                    $finishedProduct->currentPrice->update([
                        'purchase_price' => $newHpp,
                    ]);
                } else {
                    $finishedProduct->prices()->create([
                        'unit_id' => $finishedProduct->unit_id,
                        'purchase_price' => $newHpp,
                        'retail_price' => 0,
                        'is_current' => true,
                    ]);
                }
            }

            // 4. Record Financial Journal (Double-Entry)
            $materialCostCents = (int) round((float) ($production->total_cost ?? 0) * 100);
            $overheadAppliedCents = (int) round((float) $finishedProduct->overhead_rate_per_unit * $production->actual_yield);

            $journalData = new JournalEntryData(
                items: [
                    // Debit 1302: Persediaan Finished Goods = material_cost + overhead_applied
                    new JournalItemData(
                        account_id: Account::findByCode('1302')->id,
                        amount: $materialCostCents + $overheadAppliedCents,
                        type: 'debit'
                    ),
                    // Credit 1301: Persediaan Raw Materials = material_cost
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
                date: $production->date,
                description: "Productsi selesai: {$production->sku}",
                journalable: $production
            );

            $this->journalService->record($journalData);
        });
    }
}
