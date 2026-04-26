<?php

namespace Tests\Feature\Sprint2;

use App\Actions\RecordStockMovement;
use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Produk;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Satuan;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use App\Services\JournalService;
use App\Services\StornoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StornoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Satuan $satuan;

    protected Produk $produk;

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
        Account::create(['code' => '1302', 'name' => 'Persediaan Barang Jadi', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1301', 'name' => 'Persediaan', 'type' => 'asset', 'balance_type' => 'debit']); // Generic Inventory
        Account::create(['code' => '4102', 'name' => 'Pendapatan Lainnya', 'type' => 'income', 'balance_type' => 'credit']); // Surplus
        Account::create(['code' => '6201', 'name' => 'Biaya Kerusakan', 'type' => 'expense', 'balance_type' => 'debit']); // Shrinkage

        $this->satuan = Satuan::create(['nama' => 'PCS', 'simbol' => 'PCS']);
        $this->produk = Produk::create([
            'sku' => 'TEST-SKU',
            'nama' => 'Test Product',
            'satuan_id' => $this->satuan->id,
            'track_stock' => true,
        ]);

        // Add Initial Stock
        Stock::create([
            'produk_id' => $this->produk->id,
            'last_satuan_id' => $this->satuan->id,
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
            'tanggal' => now(),
            'total_amount' => 1000.00,
            'status' => 'draft',
            'payment_method' => 'cash',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'produk_id' => $this->produk->id,
            'satuan_id' => $this->satuan->id,
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
            'produk_id' => $this->produk->id,
            'type' => 'out',
            'jumlah' => 5,
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
            'produk_id' => $this->produk->id,
            'type' => 'in',
            'jumlah' => 5,
            'reference_id' => $sale->id,
            'keterangan' => 'STORNO: Customer Cancelled',
        ]);
    }

    /** @test */
    public function test_storno_stock_opname_reverses_journals_and_stock()
    {
        Log::spy();

        // 1. Create Stock Opname (Shrinkage)
        $opname = StockOpname::create([
            'tanggal' => now(),
            'keterangan' => 'Monthly Check',
            'status' => 'draft',
        ]);

        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'satuan_id' => $this->satuan->id,
            'system_qty' => 10,
            'physical_qty' => 8, // Shrinkage of 2
            'harga_satuan' => 10000, // 100.00
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
            'description' => "Penyesuaian stok Opname #{$opname->id} item {$this->produk->id}",
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
            'produk_id' => $this->produk->id,
            'type' => 'in',
            'jumlah' => 2,
            'reference_id' => $opname->id,
            'keterangan' => 'STORNO: Entry Error',
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
                    'produk_id' => $item->produk_id,
                    'satuan_id' => $item->satuan_id,
                    'type' => $diff > 0 ? 'in' : 'out',
                    'jumlah' => abs($diff),
                    'reference_type' => 'stock_opname',
                    'reference_id' => $opname->id,
                    'keterangan' => 'Opname Adjustment',
                ]);

                // Journal
                $nilaiSelisih = (int) round(abs($diff) * $item->harga_satuan);
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
                    description: "Penyesuaian stok Opname #{$opname->id} item {$item->produk_id}",
                    tanggal: $opname->tanggal,
                    journalable: $opname
                ));
            }
        }
    }
}
