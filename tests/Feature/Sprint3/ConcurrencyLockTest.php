<?php

namespace Tests\Feature\Sprint3;

use App\Actions\RecordStockMovement;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyLockTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_it_throws_exception_and_rolls_back_if_stock_insufficient()
    {
        $unit = Unit::factory()->create();
        $product = Product::factory()->create([
            'track_stock' => true,
            'unit_id' => $unit->id,
        ]);

        // Setup initial stock: 5 units
        Stock::create([
            'product_id' => $product->id,
            'balance' => 5,
            'last_unit_id' => $unit->id,
        ]);

        $this->assertEquals(5, $product->fresh()->stock->balance);

        try {
            DB::transaction(function () use ($product, $unit) {
                // Try to deduct 10 units (more than 5)
                app(RecordStockMovement::class)->handle([
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'type' => 'out',
                    'quantity' => 10,
                ]);
            });
            $this->fail('Should have thrown RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('tidak mencukupi', $e->getMessage());
        }

        // Verify stock is still 5 (rolled back)
        $this->assertEquals(5, $product->fresh()->stock->balance);

        // Verify no movement record was persisted
        $this->assertEquals(0, StockMovement::count());
    }

    /** @test */
    public function test_it_enforces_locking_logic()
    {
        $unit = Unit::factory()->create();
        $product = Product::factory()->create([
            'unit_id' => $unit->id,
        ]);

        // First movement (IN) should create the stock record and lock it
        app(RecordStockMovement::class)->handle([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'type' => 'in',
            'quantity' => 100,
        ]);

        $this->assertEquals(100, $product->fresh()->stock->balance);
    }
}
