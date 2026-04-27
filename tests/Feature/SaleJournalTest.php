<?php

namespace Tests\Feature;

use App\Exceptions\MissingCOGSException;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SaleJournalTest extends TestCase
{
    use RefreshDatabase;

    protected Unit $unit;

    protected Product $product1;

    protected Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup COA required for sales integration
        Account::create(['code' => '1102', 'name' => 'Piutang Usaha', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '4101', 'name' => 'Penjualan', 'type' => 'income', 'balance_type' => 'credit']);
        Account::create(['code' => '5101', 'name' => 'HPP', 'type' => 'expense', 'balance_type' => 'debit']);
        Account::create(['code' => '1302', 'name' => 'Persediaan Finished Goods', 'type' => 'asset', 'balance_type' => 'debit']);

        $this->unit = Unit::create(['name' => 'PCS', 'symbol' => 'PCS']);

        // Ensure Product has unit_id to satisfy StockMovementObserver
        $this->product1 = Product::create(['sku' => 'SKU-001', 'name' => 'Product 1', 'unit_id' => $this->unit->id, 'track_stock' => false]);
        $this->product2 = Product::create(['sku' => 'SKU-002', 'name' => 'Product 2', 'unit_id' => $this->unit->id, 'track_stock' => false]);
    }

    /**
     * Test successful dual-entry journaling with AUTO-COMPUTED COGS.
     */
    public function test_sale_auto_computes_cogs_on_completion(): void
    {
        // Use spy to ignore unrelated Log::info calls from other observers
        Log::spy();

        $sale = Sale::create([
            'invoice_number' => 'INV-AUTO',
            'date' => '2024-04-19',
            'total_amount' => 1000.00,
            'status' => 'draft',
            'payment_method' => 'cash',
        ]);

        // Add items to trigger computation
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $this->product1->id, 'unit_id' => $this->unit->id, 'qty' => 2, 'cost' => 150.00, 'price' => 200, 'subtotal' => 400]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $this->product2->id, 'unit_id' => $this->unit->id, 'qty' => 1, 'cost' => 100.50, 'price' => 150, 'subtotal' => 150]);

        $sale->update(['status' => 'completed']);

        // Verify cogs_amount was computed and saved
        $this->assertEquals(40050, $sale->fresh()->cogs_amount);

        // Verify Journal COGS
        $cogsJournal = JournalEntry::where('ref_number', "SALE-20240419-{$sale->id}-COGS")->firstOrFail();
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $cogsJournal->id,
            'account_id' => Account::where('code', '5101')->first()->id,
            'debit' => 40050,
        ]);
    }

    /**
     * Test that missing items block the sale status update.
     */
    public function test_sale_blocks_if_no_items(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV-EMPTY',
            'date' => '2024-04-19',
            'total_amount' => 1000.00,
            'status' => 'draft',
            'payment_method' => 'cash',
        ]);

        $this->expectException(MissingCOGSException::class);
        $this->expectExceptionMessage('Penjualan tanpa item tidak dapat diselesaikan.');

        $sale->update(['status' => 'completed']);
    }

    /**
     * Test that manual cogs_amount is overwritten by computed items cost.
     */
    public function test_sale_overwrites_manual_cogs_amount(): void
    {
        Log::spy();

        $sale = Sale::create([
            'invoice_number' => 'INV-OVERWRITE',
            'date' => '2024-04-19',
            'total_amount' => 500.00,
            'cogs_amount' => 99999, // Manual/junk value
            'status' => 'draft',
            'payment_method' => 'cash',
        ]);

        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $this->product1->id, 'unit_id' => $this->unit->id, 'qty' => 1, 'cost' => 100.00, 'price' => 150, 'subtotal' => 150]);

        $sale->update(['status' => 'completed']);

        $this->assertEquals(10000, $sale->fresh()->cogs_amount);
    }

    /**
     * Test that journal failure does not rollback sale status after validation/computation passes.
     */
    public function test_sale_does_not_rollback_on_journal_failure_with_auto_compute(): void
    {
        Log::spy();

        Account::where('code', '1102')->delete();

        $sale = Sale::create([
            'invoice_number' => 'INV-FAIL-SAFE',
            'date' => '2024-04-19',
            'total_amount' => 500.00,
            'status' => 'draft',
            'payment_method' => 'cash',
        ]);

        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $this->product1->id, 'unit_id' => $this->unit->id, 'qty' => 1, 'cost' => 100.00, 'price' => 150, 'subtotal' => 150]);

        $sale->update(['status' => 'completed']);

        // Check if computation happened despite journaling failure
        $this->assertEquals(10000, $sale->fresh()->cogs_amount);
        $this->assertEquals('completed', $sale->fresh()->status);

        // Check that at least one journal error was logged (using the spy)
        Log::shouldHaveReceived('error')->atLeast()->once();
    }
}
