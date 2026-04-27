<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementObserverTest extends TestCase
{
    use RefreshDatabase;

    private Unit $pcs;

    private Unit $box;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pcs = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs']);
        $this->box = Unit::create(['name' => 'Box', 'symbol' => 'box']);

        // 1 box = 10 pcs
        UnitConversion::create([
            'unit_id' => $this->box->id,
            'target_unit_id' => $this->pcs->id,
            'ratio' => 10,
        ]);

        // Product base unit is PCS
        $this->product = Product::factory()->create(['unit_id' => $this->pcs->id]);
    }

    public function test_movement_created_updates_balance_same_unit(): void
    {
        StockMovement::create([
            'product_id' => $this->product->id,
            'unit_id' => $this->pcs->id,
            'type' => 'in',
            'quantity' => 50,
        ]);

        $stock = Stock::where('product_id', $this->product->id)->first();
        $this->assertEquals(50.0, (float) $stock->balance);
    }

    public function test_movement_created_updates_balance_with_conversion(): void
    {
        // Add 2 boxes (should be 20 pcs)
        StockMovement::create([
            'product_id' => $this->product->id,
            'unit_id' => $this->box->id,
            'type' => 'in',
            'quantity' => 2,
        ]);

        $stock = Stock::where('product_id', $this->product->id)->first();
        $this->assertEquals(20.0, (float) $stock->balance);
    }

    public function test_movement_out_decreases_balance(): void
    {
        // Initial 100 pcs
        StockMovement::create([
            'product_id' => $this->product->id,
            'unit_id' => $this->pcs->id,
            'type' => 'in',
            'quantity' => 100,
        ]);

        // Out 1 box (10 pcs)
        StockMovement::create([
            'product_id' => $this->product->id,
            'unit_id' => $this->box->id,
            'type' => 'out',
            'quantity' => 1,
        ]);

        $stock = Stock::where('product_id', $this->product->id)->first();
        $this->assertEquals(90.0, (float) $stock->balance);
    }

    public function test_movement_deleted_reverts_balance(): void
    {
        $movement = StockMovement::create([
            'product_id' => $this->product->id,
            'unit_id' => $this->pcs->id,
            'type' => 'in',
            'quantity' => 100,
        ]);

        $this->assertEquals(100.0, (float) $this->product->fresh()->stock->balance);

        $movement->delete();

        $this->assertEquals(0.0, (float) Stock::where('product_id', $this->product->id)->first()->balance);
    }

    public function test_inverse_conversion_handling(): void
    {
        // Product base unit is BOX
        $productBox = Product::factory()->create(['unit_id' => $this->box->id]);

        // Add 20 pcs (should be 2 boxes)
        StockMovement::create([
            'product_id' => $productBox->id,
            'unit_id' => $this->pcs->id,
            'type' => 'in',
            'quantity' => 20,
        ]);

        $stock = Stock::where('product_id', $productBox->id)->first();
        $this->assertEquals(2.0, (float) $stock->balance);
    }
}
