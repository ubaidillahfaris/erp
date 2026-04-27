<?php

namespace Tests\Feature\Sprint2;

use App\Actions\RecordStockMovement;
use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Unit;
use App\Models\User;
use App\Services\JournalService;
use App\Services\StornoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StornoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Unit $unit;

    protected Product $product;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Setup Accounts
        Account::create(['code' => '1102', 'name' => 'Piutang Usaha', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '4101', 'name' => 'Penjualan', 'type' => 'income', 'balance_type' => 'credit']);
        Account::create(['code' => '5101', 'name' => 'HPP', 'type' => 'expense', 'balance_type' => 'debit']);
        Account::create(['code' => '1302', 'name' => 'Persediaan Finished Goods', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1301', 'name' => 'Persediaan', 'type' => 'asset', 'balance_type' => 'debit']); // Generic Inventory
        Account::create(['code' => '4102', 'name' => 'Pendapatan Lainnya', 'type' => 'income', 'balance_type' => 'credit']); // Surplus
        Account::create(['code' => '6201', 'name' => 'Biaya Kerusakan', 'type' => 'expense', 'balance_type' => 'debit']); // Shrinkage

        $this->unit = Unit::create(['name' => 'PCS', 'symbol' => 'PCS']);
        $this->product = Product::create([
            'sku' => 'TEST-SKU',
            'name' => 'Test Product',
            'unit_id' => $this->unit->id,
            'track_stock' => true,
        ]);

        // Add Initial Stock
        Stock::create([
            'product_id' => $this->product->id,
            'last_unit_id' => $this->unit->id,
            'balance' => 100,
        ]);
    }

    /** @test */
    public function test_storno_sale_reverses_journals_and_stock()
    {
        Log::spy();

        // 1. Create a Sale
        $sale = Sale::create([
            'invoice_number' => 'INV-001',
            'date' => now(),
            'total_amount' => 1000.00,
            'status' => 'draft',
            'payment_method' => 'cash',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'qty' => 5,
            'cost' => 100.00,
            'price' => 200.00,
            'subtotal' => 1000.00,
        ]);

        // Complete Sale triggers Journals and Stock Out (via Observers)
        $sale->update(['status' => 'completed']);

        // Verify original journals exist
        $this->assertEquals(2, JournalEntry::where('journalable_id', $sale->id)->count()); // Revenue + COGS

        // Check stock out
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => 5,
            'reference_id' => $sale->id,
        ]);

        // 2. Perform Storno
        $stornoService = app(StornoService::class);
        $stornoService->perform($sale, 'Customer Cancelled');

        // 3. Verify Reversal
        $sale->refresh();
        $this->assertEquals('voided', $sale->status);
        $this->assertNotNull($sale->storno_at);
        $this->assertEquals('Customer Cancelled', $sale->storno_reason);

        // Verify Reversal Journals
        // Original was 2, Storno adds 2 more = 4
        $this->assertEquals(4, JournalEntry::where('journalable_id', $sale->id)->count());

        $stornoEntries = JournalEntry::where('journalable_id', $sale->id)
            ->where('description', 'like', 'STORNO:%')
            ->get();

        $this->assertEquals(2, $stornoEntries->count());

        // Verify Stock Return
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 5,
            'reference_id' => $sale->id,
            'notes' => 'STORNO: Customer Cancelled',
        ]);
    }

    /** @test */
    public function test_storno_stock_opname_reverses_journals_and_stock()
    {
        Log::spy();

        // 1. Create Stock Opname (Shrinkage)
        $opname = StockOpname::create([
            'date' => now(),
            'notes' => 'Monthly Check',
            'status' => 'draft',
        ]);

        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'system_qty' => 10,
            'physical_qty' => 8, // Shrinkage of 2
            'unit_price' => 10000, // 100.00
        ]);

        // Complete triggers journals and stock movements
        // In real app, this is handled in Controller or Service.
        // Our controller has the logic, let's use the controller or simulate it.
        // For integration test, we simulate what the controller does.

        // Simulating the controller logic for finalization
        $opname->update(['status' => 'completed']);

        // Manually trigger the logic that would be in the controller
        // (or we could use the route, but this is faster for logic check)
        $this->simulateFinalizeOpname($opname);

        // Verify shrinkage journal exists
        $this->assertDatabaseHas('journal_entries', [
            'journalable_id' => $opname->id,
            'description' => "Penyesuaian stok Opname #{$opname->id} item {$this->product->id}",
        ]);

        // 2. Perform Storno
        $stornoService = app(StornoService::class);
        $stornoService->perform($opname, 'Entry Error');

        // 3. Verify Reversal
        $opname->refresh();
        $this->assertEquals('storno', $opname->status);

        // Verify Reversal Journal
        $this->assertDatabaseHas('journal_entries', [
            'journalable_id' => $opname->id,
            'description' => 'STORNO: Entry Error',
        ]);

        // Verify Stock Reversal
        // Original was 'out' for 2
        // Storno should be 'in' for 2
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 2,
            'reference_id' => $opname->id,
            'notes' => 'STORNO: Entry Error',
        ]);
    }

    /**
     * Helper to simulate the logic in StockOpnameController@finalizeOpname
     */
    private function simulateFinalizeOpname(StockOpname $opname)
    {
        foreach ($opname->items as $item) {
            $diff = (float) $item->physical_qty - (float) $item->system_qty;
            if (abs($diff) > 0.000001) {
                app(RecordStockMovement::class)->handle([
                    'product_id' => $item->product_id,
                    'unit_id' => $item->unit_id,
                    'type' => $diff > 0 ? 'in' : 'out',
                    'quantity' => abs($diff),
                    'reference_type' => 'stock_opname',
                    'reference_id' => $opname->id,
                    'notes' => 'Opname Adjustment',
                ]);

                // Journal
                $nilaiSelisih = (int) round(abs($diff) * $item->unit_price);
                $itemsData = [];
                $persediaanAccount = Account::where('code', '1301')->first();

                if ($diff > 0) {
                    $incomeAccount = Account::where('code', '4102')->first();
                    $itemsData = [
                        new JournalItemData($persediaanAccount->id, $nilaiSelisih, 'debit'),
                        new JournalItemData($incomeAccount->id, $nilaiSelisih, 'credit'),
                    ];
                } else {
                    $expenseAccount = Account::where('code', '6201')->first();
                    $itemsData = [
                        new JournalItemData($expenseAccount->id, $nilaiSelisih, 'debit'),
                        new JournalItemData($persediaanAccount->id, $nilaiSelisih, 'credit'),
                    ];
                }

                app(JournalService::class)->record(new JournalEntryData(
                    items: $itemsData,
                    description: "Penyesuaian stok Opname #{$opname->id} item {$item->product_id}",
                    date: $opname->date,
                    journalable: $opname
                ));
            }
        }
    }
}
