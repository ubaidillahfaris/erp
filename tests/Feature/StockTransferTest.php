<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Warehouse $wh1;

    protected Warehouse $wh2;

    protected Product $product;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        Permission::firstOrCreate(['name' => 'manage stock']);
        $this->admin->givePermissionTo('manage stock');

        $this->wh1 = Warehouse::factory()->create(['name' => 'Gudang 1', 'code' => 'WH1']);
        $this->wh2 = Warehouse::factory()->create(['name' => 'Gudang 2', 'code' => 'WH2']);

        $this->unit = Unit::factory()->create(['name' => 'pcs']);
        $this->product = Product::factory()->create([
            'name' => 'Produk Test',
            'unit_id' => $this->unit->id,
        ]);

        // Add initial stock to WH1
        Stock::create([
            'product_id' => $this->product->id,
            'last_unit_id' => $this->unit->id,
            'warehouse_id' => $this->wh1->id,
            'balance' => 100,
        ]);
    }

    public function test_can_create_transfer_draft()
    {
        $data = [
            'from_warehouse_id' => $this->wh1->id,
            'to_warehouse_id' => $this->wh2->id,
            'notes' => 'Test transfer',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'quantity_requested' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/stock-transfers', $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', ['status' => 'draft']);

        // Stock should NOT change yet
        $this->assertEquals(100, Stock::where('product_id', $this->product->id)->where('warehouse_id', $this->wh1->id)->value('balance'));
    }

    public function test_can_dispatch_transfer()
    {
        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->wh1->id,
            'to_warehouse_id' => $this->wh2->id,
            'status' => 'draft',
            'transfer_number' => 'TRF-001',
            'created_by' => $this->admin->id,
        ]);

        $transfer->items()->create([
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity_requested' => 10,
        ]);

        $response = $this->actingAs($this->admin)->post("/stock-transfers/{$transfer->id}/dispatch");

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'status' => 'in_transit']);

        // Stock in WH1 should decrease
        $this->assertEquals(90, Stock::where('product_id', $this->product->id)->where('warehouse_id', $this->wh1->id)->value('balance'));

        // Stock in WH2 should NOT increase yet
        $this->assertEquals(0, Stock::where('product_id', $this->product->id)->where('warehouse_id', $this->wh2->id)->value('balance') ?? 0);
    }

    public function test_can_receive_transfer()
    {
        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->wh1->id,
            'to_warehouse_id' => $this->wh2->id,
            'status' => 'in_transit',
            'transfer_number' => 'TRF-002',
            'created_by' => $this->admin->id,
        ]);

        $item = $transfer->items()->create([
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity_requested' => 10,
        ]);

        // Manually decrease stock for setup (as if dispatched)
        Stock::where('product_id', $this->product->id)->where('warehouse_id', $this->wh1->id)->decrement('balance', 10);

        $data = [
            'items' => [
                [
                    'id' => $item->id,
                    'quantity_received' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post("/stock-transfers/{$transfer->id}/receive", $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'status' => 'completed']);

        // Stock in WH2 should increase
        $this->assertEquals(10, Stock::where('product_id', $this->product->id)->where('warehouse_id', $this->wh2->id)->value('balance'));
    }
}
