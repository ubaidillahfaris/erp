<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryDispositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup accounts for journal entries
        Account::create(['code' => '1302', 'name' => 'Finished Goods', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '5102', 'name' => 'Loss on Inventory', 'type' => 'expense', 'balance_type' => 'debit']);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    protected function createFullSale($warehouse, $product, $qty = 10)
    {
        $customer = Customer::factory()->create();
        $unit = Unit::factory()->create();

        $sale = Sale::factory()->create([
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'total_amount' => 1000,
        ]);

        $saleItem = SaleItem::forceCreate([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'qty' => $qty,
            'price' => 100,
            'cost' => 50,
            'subtotal' => $qty * 100,
        ]);

        return [$sale, $saleItem, $unit];
    }

    public function test_returned_goods_go_to_quarantine_warehouse()
    {
        $warehouse = Warehouse::factory()->create(['is_default' => true]);
        $product = Product::factory()->create(['type' => 'finished_good']);

        [$sale, $saleItem, $unit] = $this->createFullSale($warehouse, $product);

        // Initial stock in main warehouse
        Stock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'condition' => 'good',
            'balance' => 10,
            'last_unit_id' => $unit->id,
            'is_sellable' => true,
        ]);

        // Create Credit Note
        $response = $this->post(route('credit-notes.store'), [
            'sale_id' => $sale->id,
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity_returned' => 2,
                    'reason' => 'Damaged',
                ],
            ],
        ]);

        $creditNote = CreditNote::first();
        $this->post(route('credit-notes.post', $creditNote));

        // Check if goods are in Quarantine warehouse
        $quarantineWarehouse = Warehouse::where('code', 'WH-QRT')->first();
        $this->assertNotNull($quarantineWarehouse);

        $quarantineStock = Stock::where('product_id', $product->id)
            ->where('warehouse_id', $quarantineWarehouse->id)
            ->where('condition', 'quarantine')
            ->first();

        $this->assertEquals(2, $quarantineStock->balance);
        $this->assertFalse($quarantineStock->is_sellable);
    }

    public function test_restock_disposition_moves_goods_to_main_warehouse()
    {
        $warehouse = Warehouse::factory()->create(['code' => 'WH-MAIN', 'is_default' => true]);
        $qrtWarehouse = Warehouse::create(['code' => 'WH-QRT', 'name' => 'Quarantine']);
        $product = Product::factory()->create();

        [$sale, $saleItem, $unit] = $this->createFullSale($warehouse, $product);

        // Stock in quarantine
        Stock::create([
            'product_id' => $product->id,
            'warehouse_id' => $qrtWarehouse->id,
            'condition' => 'quarantine',
            'balance' => 5,
            'last_unit_id' => $unit->id,
            'is_sellable' => false,
        ]);

        $creditNote = CreditNote::create(['sale_id' => $sale->id, 'credit_note_number' => 'CN-001', 'status' => 'posted', 'total_amount' => 1000]);
        $cnItem = CreditNoteItem::create([
            'credit_note_id' => $creditNote->id,
            'product_id' => $product->id,
            'sale_item_id' => $saleItem->id,
            'quantity_returned' => 5,
            'price' => 100,
        ]);

        $response = $this->post(route('dispositions.store'), [
            'credit_note_item_id' => $cnItem->id,
            'action' => 'restock',
            'quantity' => 3,
            'to_warehouse_id' => $warehouse->id,
            'notes' => 'Ternyata masih bagus',
        ]);

        $response->assertSessionHasNoErrors();

        // Quarantine stock should be 2 (5 - 3)
        $this->assertEquals(2, Stock::where('warehouse_id', $qrtWarehouse->id)->where('condition', 'quarantine')->value('balance'));

        // Main warehouse stock should be 3
        $this->assertEquals(3, Stock::where('warehouse_id', $warehouse->id)->where('condition', 'good')->value('balance'));
    }

    public function test_write_off_disposition_records_journal()
    {
        $warehouse = Warehouse::factory()->create();
        $qrtWarehouse = Warehouse::create(['code' => 'WH-QRT', 'name' => 'Quarantine']);
        $product = Product::factory()->create();

        [$sale, $saleItem, $unit] = $this->createFullSale($warehouse, $product);

        // Stock in quarantine
        Stock::create([
            'product_id' => $product->id,
            'warehouse_id' => $qrtWarehouse->id,
            'condition' => 'quarantine',
            'balance' => 5,
            'last_unit_id' => $unit->id,
            'is_sellable' => false,
        ]);

        $creditNote = CreditNote::create(['sale_id' => $sale->id, 'credit_note_number' => 'CN-001', 'status' => 'posted', 'total_amount' => 1000]);
        $cnItem = CreditNoteItem::create(['credit_note_id' => $creditNote->id, 'product_id' => $product->id, 'sale_item_id' => $saleItem->id, 'quantity_returned' => 5, 'price' => 100]);

        $response = $this->post(route('dispositions.store'), [
            'credit_note_item_id' => $cnItem->id,
            'action' => 'write_off',
            'quantity' => 5,
            'notes' => 'Hancur parah',
        ]);

        $this->assertEquals(0, Stock::where('warehouse_id', $qrtWarehouse->id)->where('condition', 'quarantine')->value('balance'));

        // Check journal entries (Value = 5 items * 50 cost = 250)
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => 'inventory_disposition',
        ]);

        $this->assertDatabaseHas('journal_items', [
            'debit' => 250,
            'credit' => 0,
        ]);
    }
}
