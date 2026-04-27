<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CompleteProduction;
use App\Exceptions\MissingOverheadRateException;
use App\Models\Account;
use App\Models\Bom;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionItem;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionJournalTest extends TestCase
{
    use RefreshDatabase;

    private Account $rawMaterialAcc;

    private Account $finishedGoodAcc;

    private Account $overheadAcc;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Account codes
        $this->rawMaterialAcc = Account::create([
            'code' => '1301',
            'name' => 'Persediaan Raw Materials',
            'type' => 'asset',
            'balance_type' => 'debit',
        ]);

        $this->finishedGoodAcc = Account::create([
            'code' => '1302',
            'name' => 'Persediaan Finished Goods',
            'type' => 'asset',
            'balance_type' => 'debit',
        ]);

        $this->overheadAcc = Account::create([
            'code' => '5102',
            'name' => 'Biaya Overhead',
            'type' => 'expense',
            'balance_type' => 'debit',
        ]);
    }

    public function test_complete_production_records_balanced_journal_with_overhead(): void
    {
        $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);

        $fgProduct = Product::create([
            'sku' => 'FG-001',
            'name' => 'Finished Good',
            'type' => 'finished_good',
            'unit_id' => $unit->id,
            'overhead_rate_per_unit' => 500, // Rp 5,00
            'is_active' => true,
            'track_stock' => false,
        ]);

        $bom = Bom::create([
            'product_id' => $fgProduct->id,
            'sku' => 'BOM-001',
            'name' => 'BOM 001',
            'expected_yield' => 10,
        ]);

        $production = Production::create([
            'sku' => 'PRD-001',
            'date' => now(),
            'bom_id' => $bom->id,
            'product_id' => $fgProduct->id,
            'target_yield' => 10,
            'actual_yield' => 10,
            'total_cost' => 1000.00, // Material cost
            'status' => 'in_progress',
        ]);

        // Add a production item (raw material usage)
        $rawMaterial = Product::create([
            'sku' => 'RAW-001',
            'name' => 'Raw Material',
            'unit_id' => $unit->id,
            'track_stock' => false,
        ]);

        ProductionItem::create([
            'production_id' => $production->id,
            'product_id' => $rawMaterial->id,
            'unit_id' => $unit->id,
            'planned_qty' => 5,
            'actual_qty' => 5,
            'unit_price' => 200,
        ]);

        // Execute action
        app(CompleteProduction::class)->handle($production);

        // Verify Journal Entry
        $entry = JournalEntry::where('journalable_type', Production::class)
            ->where('journalable_id', $production->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertCount(3, $entry->items);

        // Material Cost: 1000.00 * 100 = 100000 cents
        // Overhead Applied: 500 * 10 = 5000 cents
        // Total Finished Good: 105000 cents

        // Debit Finished Goods (1302)
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $entry->id,
            'account_id' => $this->finishedGoodAcc->id,
            'debit' => 105000,
            'credit' => 0,
        ]);

        // Credit Raw Materials (1301)
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $entry->id,
            'account_id' => $this->rawMaterialAcc->id,
            'debit' => 0,
            'credit' => 100000,
        ]);

        // Credit Overhead (5102)
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $entry->id,
            'account_id' => $this->overheadAcc->id,
            'debit' => 0,
            'credit' => 5000,
        ]);
    }

    public function test_complete_production_throws_exception_if_overhead_missing(): void
    {
        $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);

        $fgProduct = Product::create([
            'sku' => 'FG-002',
            'name' => 'Finished Good No Overhead',
            'type' => 'finished_good',
            'unit_id' => $unit->id,
            'overhead_rate_per_unit' => 0, // Missing
            'is_active' => true,
        ]);

        $bom = Bom::create([
            'product_id' => $fgProduct->id,
            'sku' => 'BOM-002',
            'name' => 'BOM 002',
            'expected_yield' => 10,
        ]);

        $production = Production::create([
            'sku' => 'PRD-002',
            'date' => now(),
            'bom_id' => $bom->id,
            'product_id' => $fgProduct->id,
            'target_yield' => 10,
            'actual_yield' => 10,
            'total_cost' => 1000.00,
            'status' => 'in_progress',
        ]);

        $this->expectException(MissingOverheadRateException::class);

        app(CompleteProduction::class)->handle($production);
    }
}
