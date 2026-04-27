<?php

namespace Tests\Feature;

use App\Actions\RecordStockMovement;
use App\Models\Account;
use App\Models\Product;
use App\Models\Restock;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Unit $unit;

    protected Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed required COA for restock journaling
        Account::create(['code' => '1101', 'name' => 'Kas', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1301', 'name' => 'Persediaan Materi', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '2101', 'name' => 'Hutang', 'type' => 'liability', 'balance_type' => 'credit']);

        $this->user = User::factory()->superadmin()->create();
        $this->actingAs($this->user);

        $this->unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
        $this->vendor = Vendor::factory()->create();
    }

    public function test_restock_creates_stock_movement_and_updates_balance()
    {
        $product = Product::factory()->create(['unit_id' => $this->unit->id]);

        $response = $this->post(route('restock.store'), [
            'date' => now()->format('Y-m-d'),
            'vendor_id' => $this->vendor->id,
            'status_pembayaran' => 'lunas',
            'total_bayar' => 10000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 10,
                    'unit_price' => 1000,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 10,
            'reference_type' => 'restock',
        ]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'balance' => 10.0000,
        ]);
    }

    public function test_manual_adjustment_updates_stock()
    {
        $product = Product::factory()->create(['unit_id' => $this->unit->id]);

        $response = $this->post(route('stock.adjustment'), [
            'product_id' => $product->id,
            'unit_id' => $this->unit->id,
            'type' => 'in',
            'quantity' => 50,
            'notes' => 'Initial stock opname',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'balance' => 50.0000,
        ]);

        // Adjustment out
        $this->post(route('stock.adjustment'), [
            'product_id' => $product->id,
            'unit_id' => $this->unit->id,
            'type' => 'out',
            'quantity' => 10,
            'notes' => 'Waste',
        ]);

        $this->assertEquals(40, (float) Stock::where('product_id', $product->id)->first()->balance);

        // Test Zero Adjustment
        $this->post(route('stock.adjustment'), [
            'product_id' => $product->id,
            'unit_id' => $this->unit->id,
            'type' => 'in',
            'quantity' => 0,
            'notes' => 'Zero check',
        ]);
        $this->assertEquals(40, (float) Stock::where('product_id', $product->id)->first()->balance);
    }

    public function test_deleting_restock_removes_movements_and_reverts_stock()
    {
        $product = Product::factory()->create(['unit_id' => $this->unit->id]);

        $restock = Restock::create([
            'date' => now(),
            'status_pembayaran' => 'lunas',
            'total_biaya' => 1000,
            'vendor_id' => $this->vendor->id,
        ]);

        app(RecordStockMovement::class)->handle([
            'product_id' => $product->id,
            'unit_id' => $this->unit->id,
            'type' => 'in',
            'quantity' => 100,
            'reference_type' => 'restock',
            'reference_id' => $restock->id,
        ]);

        $this->assertEquals(100, (float) $product->stock->balance);

        // Deleting restock should delete the movements via observer
        $restock->delete();

        $this->assertEquals(0, (float) Stock::where('product_id', $product->id)->first()->balance);
        $this->assertEquals(0, StockMovement::count());
    }
}
