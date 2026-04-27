<?php

namespace Tests\Feature;

use App\Actions\RecordStockMovement;
use App\Models\Account;
use App\Models\Product;
use App\Models\Restock;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed required accounts for integration
        Account::create(['code' => '1101', 'name' => 'Kas', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1301', 'name' => 'Persediaan Materi', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1302', 'name' => 'Persediaan FG', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '2101', 'name' => 'Hutang', 'type' => 'liability', 'balance_type' => 'credit']);
    }

    public function test_can_save_stock_opname_as_draft(): void
    {
        $user = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $product = Product::factory()->create(['unit_id' => $unit->id]);

        $response = $this->actingAs($user)->post(route('stock-opname.store'), [
            'date' => now()->format('Y-m-d'),
            'notes' => 'Opname Draft',
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'system_qty' => 10,
                    'physical_qty' => 8,
                ],
            ],
        ]);

        $response->assertRedirect(route('stock-opname.index'));
        $this->assertDatabaseHas('stock_opnames', ['status' => 'draft']);

        // Stock should NOT change for draft
        $stock = Stock::where('product_id', $product->id)->first();
        $this->assertEquals(0, (float) ($stock->balance ?? 0));
    }

    public function test_completing_stock_opname_creates_adjustments(): void
    {
        $user = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $product = Product::factory()->create(['unit_id' => $unit->id]);

        // Setup initial stock
        app(RecordStockMovement::class)->handle([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'type' => 'in',
            'quantity' => 10,
            'notes' => 'Initial',
        ]);

        $this->assertEquals(10, (float) Stock::where('product_id', $product->id)->first()->balance);

        $response = $this->actingAs($user)->post(route('stock-opname.store'), [
            'date' => now()->format('Y-m-d'),
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'system_qty' => 10,
                    'physical_qty' => 15, // Found more physically
                ],
            ],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('stock_opnames', ['status' => 'completed']);
        $this->assertDatabaseHas('stock_opname_items', [
            'product_id' => $product->id,
            'physical_qty' => 15,
            'system_qty' => 10,
        ]);

        // Stock should change to 15
        $stock = Stock::where('product_id', $product->id)->first();
        $this->assertNotNull($stock, 'Stock record should exist');
        $this->assertEquals(15, (float) $stock->balance);

        // Verify adjustment movement exists
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 5,
            'reference_type' => 'stock_opname',
        ]);
    }

    public function test_can_settle_restock_debt(): void
    {
        $user = User::factory()->superadmin()->create();
        $vendor = Vendor::factory()->create();
        $restock = Restock::create([
            'date' => now(),
            'total_biaya' => 100000,
            'total_bayar' => 0,
            'status_pembayaran' => 'hutang',
            'vendor_id' => $vendor->id,
        ]);

        $response = $this->actingAs($user)->post(route('restock.settle', $restock));

        $response->assertRedirect();
        $restock->refresh();
        $this->assertEquals('lunas', $restock->status_pembayaran);
        $this->assertEquals(100000, $restock->total_bayar);
    }

    public function test_can_storno_completed_stock_opname(): void
    {
        $user = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $product = Product::factory()->create(['unit_id' => $unit->id]);

        // Initial stock: 10
        app(RecordStockMovement::class)->handle([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'type' => 'in',
            'quantity' => 10,
            'notes' => 'Initial',
        ]);

        // Complete opname: physical 15 (diff +5)
        $this->actingAs($user)->post(route('stock-opname.store'), [
            'date' => now()->format('Y-m-d'),
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'system_qty' => 10,
                    'physical_qty' => 15,
                ],
            ],
        ]);

        $this->assertEquals(15, (float) Stock::where('product_id', $product->id)->first()->balance);
        $opname = StockOpname::latest()->first();

        // Perform Storno
        $response = $this->actingAs($user)->post(route('stock-opname.storno', $opname), [
            'reason' => 'Wrong entry',
        ]);

        $response->assertRedirect();
        $opname->refresh();

        $this->assertEquals('storno', $opname->status);
        $this->assertNotNull($opname->storno_at);
        $this->assertEquals('Wrong entry', $opname->storno_reason);

        // Stock should be back to 10
        $this->assertEquals(10, (float) Stock::where('product_id', $product->id)->first()->balance);

        // Verify counter movement exists
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 5,
            'reference_type' => 'stock_opname',
            'reference_id' => $opname->id,
        ]);
    }

    public function test_can_reopen_completed_stock_opname(): void
    {
        $user = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $product = Product::factory()->create(['unit_id' => $unit->id]);

        // Initial stock: 10
        app(RecordStockMovement::class)->handle([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'type' => 'in',
            'quantity' => 10,
        ]);

        // Complete opname: physical 12 (diff +2)
        $this->actingAs($user)->post(route('stock-opname.store'), [
            'date' => now()->format('Y-m-d'),
            'status' => 'completed',
            'items' => [
                ['product_id' => $product->id, 'unit_id' => $unit->id, 'system_qty' => 10, 'physical_qty' => 12],
            ],
        ]);

        $this->assertEquals(12, (float) Stock::where('product_id', $product->id)->first()->balance);
        $opname = StockOpname::latest()->first();

        // Reopen (Edit Kembali)
        $response = $this->actingAs($user)->post(route('stock-opname.reopen', $opname));

        $response->assertRedirect(route('stock-opname.edit', $opname));
        $opname->refresh();

        $this->assertEquals('draft', $opname->status);
        // Stock should be back to 10
        $this->assertEquals(10, (float) Stock::where('product_id', $product->id)->first()->balance);
    }
}
