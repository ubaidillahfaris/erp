<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bom;
use App\Models\Customer;
use App\Models\CustomerCreditSetting;
use App\Models\CustomerStatus;
use App\Models\CustomerType;
use App\Models\JournalEntry;
use App\Models\Payable;
use App\Models\Produk;
use App\Models\Price;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Satuan;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\Production;
use App\Models\User;
use App\Services\StornoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ObserverStornoTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Satuan $satuan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->superadmin()->create();
        $this->actingAs($this->user);

        $this->satuan = Satuan::create(['nama' => 'PCS', 'simbol' => 'pcs']);

        // Required Accounts
        Account::create(['code' => '1101', 'name' => 'Cash', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1102', 'name' => 'Receivable', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1302', 'name' => 'Finished Goods', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '4101', 'name' => 'Sales', 'type' => 'income', 'balance_type' => 'credit']);
        Account::create(['code' => '5101', 'name' => 'COGS', 'type' => 'expense', 'balance_type' => 'debit']);
        Account::create(['code' => '1103', 'name' => 'Inventory Adjustment', 'type' => 'asset', 'balance_type' => 'debit']);
    }

    public function test_sale_observer_throws_exception_on_credit_sale_without_customer()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Credit sale harus ada customer');

        Sale::create([
            'invoice_number' => 'ERR-001',
            'tanggal' => now(),
            'total_amount' => 10000,
            'payment_method' => 'credit',
            'status' => 'pending',
        ]);
    }

    public function test_sale_observer_checks_credit_limit()
    {
        $status = CustomerStatus::create(['name' => 'Active']);
        $type = CustomerType::create(['name' => 'Regular']);
        $customer = Customer::create([
            'name' => 'Limited Customer',
            'customer_status_id' => $status->id,
            'customer_type_id' => $type->id,
        ]);

        CustomerCreditSetting::create([
            'customer_id' => $customer->id,
            'allow_credit' => true,
            'credit_limit' => 5000, // Limit 5000
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Melebihi credit limit');

        request()->merge(['customer_id' => $customer->id]);
        
        Sale::create([
            'invoice_number' => 'ERR-002',
            'tanggal' => now(),
            'total_amount' => 10000, // Over limit
            'payment_method' => 'credit',
            'status' => 'pending',
        ]);
    }

    public function test_storno_service_reverses_sale_properly()
    {
        $produk = Produk::create(['sku' => 'P-001', 'nama' => 'P1', 'satuan_id' => $this->satuan->id, 'track_stock' => true]);
        Stock::create(['produk_id' => $produk->id, 'balance' => 10, 'last_satuan_id' => $this->satuan->id]);

        $sale = Sale::create([
            'invoice_number' => 'IV-TEST',
            'tanggal' => now(),
            'total_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
            'cogs_amount' => 5000,
        ]);

        $item = SaleItem::create([
            'sale_id' => $sale->id,
            'produk_id' => $produk->id,
            'satuan_id' => $this->satuan->id,
            'qty' => 2,
            'price' => 5000,
            'cost' => 2500,
            'subtotal' => 10000,
        ]);

        // Manually create some journal entries for the sale to reverse
        $entry = JournalEntry::create([
            'tanggal' => now(),
            'description' => 'Test Sale',
            'journalable_type' => Sale::class,
            'journalable_id' => $sale->id,
            'ref_number' => 'REF-1',
        ]);
        $entry->items()->create(['account_id' => Account::findByCode('1101')->id, 'debit' => 10000]);
        $entry->items()->create(['account_id' => Account::findByCode('4101')->id, 'credit' => 10000]);

        $stornoService = app(StornoService::class);
        $stornoService->perform($sale, 'Customer returned');

        $sale->refresh();
        $this->assertEquals('voided', $sale->status);
        $this->assertEquals('Customer returned', $sale->storno_reason);

        // Check Stock Reversed (10 - 2 = 8 after SaleItem created, now should be 10 again after storno)
        $this->assertEquals(10, (float) $produk->stock->refresh()->balance); 

        // Check Journal Reversed
        $this->assertDatabaseHas('journal_entries', [
            'ref_number' => 'STRN-REF-1',
            'description' => 'STORNO: Customer returned',
        ]);
    }

    public function test_storno_stock_opname()
    {
        $produk = Produk::create(['sku' => 'P-002', 'nama' => 'P2', 'satuan_id' => $this->satuan->id]);
        Stock::create(['produk_id' => $produk->id, 'balance' => 10, 'last_satuan_id' => $this->satuan->id]);

        $opname = StockOpname::create([
            'produk_id' => $produk->id,
            'satuan_id' => $this->satuan->id,
            'system_qty' => 10,
            'actual_qty' => 15,
            'adjustment_qty' => 5,
            'type' => 'surplus',
            'status' => 'completed',
            'tanggal' => now(),
        ]);

        // Create movement
        StockMovement::create([
            'produk_id' => $produk->id,
            'satuan_id' => $this->satuan->id,
            'type' => 'in',
            'jumlah' => 5,
            'reference_type' => 'stock_opname',
            'reference_id' => $opname->id,
            'keterangan' => 'Opname',
        ]);

        $stornoService = app(StornoService::class);
        $stornoService->perform($opname, 'Entry error');

        $opname->refresh();
        $this->assertEquals('storno', $opname->status);
        
        // Stock: 10 + 5 (opname) - 5 (storno) = 10
        // Wait, our test setup above manually created movement but didn't update stock balance.
        // Storno will record a new movement 'out' of 5.
        $this->assertDatabaseHas('stock_movements', [
            'produk_id' => $produk->id,
            'type' => 'out',
            'jumlah' => 5,
            'keterangan' => 'STORNO: Entry error',
        ]);
    }

    public function test_sale_deletion_removes_related_records()
    {
        $produk = Produk::create(['sku' => 'P-DEL', 'nama' => 'P-DEL', 'satuan_id' => $this->satuan->id, 'track_stock' => true]);
        Stock::create(['produk_id' => $produk->id, 'balance' => 10, 'last_satuan_id' => $this->satuan->id]);

        $sale = Sale::create([
            'invoice_number' => 'IV-DEL',
            'tanggal' => now(),
            'total_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'pending',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'produk_id' => $produk->id,
            'satuan_id' => $this->satuan->id,
            'qty' => 1,
            'price' => 10000,
            'cost' => 5000,
            'subtotal' => 10000,
        ]);

        // Verify stock movement exists
        $this->assertDatabaseHas('stock_movements', ['reference_id' => $sale->id]);

        $sale->delete();

        $this->assertDatabaseMissing('stock_movements', ['reference_id' => $sale->id]);
    }
}



