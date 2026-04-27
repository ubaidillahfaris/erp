<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_specific_conversion_takes_precedence(): void
    {
        $pack = Unit::factory()->create(['name' => 'Pack', 'symbol' => 'pack']);
        $pcs = Unit::factory()->create(['name' => 'Pcs', 'symbol' => 'pcs']);
        $renteng = Unit::factory()->create(['name' => 'Renteng', 'symbol' => 'rtg']);

        // Global conversion: 1 Renteng = 10 Pcs
        UnitConversion::create([
            'unit_id' => $renteng->id,
            'target_unit_id' => $pcs->id,
            'ratio' => 10,
            'product_id' => null,
        ]);

        $product = Product::factory()->create([
            'name' => 'Kapal Api Krim Kafe',
            'unit_id' => $pack->id,
        ]);

        // Product specific: 1 Pack = 50 Pcs
        UnitConversion::create([
            'unit_id' => $pack->id,
            'target_unit_id' => $pcs->id,
            'ratio' => 50,
            'product_id' => $product->id,
        ]);

        // Record stock movement: 5 pcs IN
        // Ratio should be 1 Pack = 50 Pcs, so 5 pcs = 5/50 = 0.1 Pack
        StockMovement::create([
            'product_id' => $product->id,
            'unit_id' => $pcs->id,
            'type' => 'in',
            'quantity' => 5,
        ]);

        $stock = Stock::where('product_id', $product->id)->first();
        $this->assertEquals(0.1, (float) $stock->balance);
    }

    public function test_fallback_to_global_conversion(): void
    {
        $pack = Unit::factory()->create(['name' => 'Pack', 'symbol' => 'pack']);
        $pcs = Unit::factory()->create(['name' => 'Pcs', 'symbol' => 'pcs']);
        $renteng = Unit::factory()->create(['name' => 'Renteng', 'symbol' => 'rtg']);

        // Global conversion: 1 Renteng = 10 Pcs
        UnitConversion::create([
            'unit_id' => $renteng->id,
            'target_unit_id' => $pcs->id,
            'ratio' => 10,
            'product_id' => null,
        ]);

        $product = Product::factory()->create([
            'name' => 'Some Other Product',
            'unit_id' => $renteng->id,
        ]);

        // Record stock movement: 5 pcs IN
        // Falling back to global ratio: 1 Renteng = 10 Pcs, so 5 pcs = 5/10 = 0.5 Renteng
        StockMovement::create([
            'product_id' => $product->id,
            'unit_id' => $pcs->id,
            'type' => 'in',
            'quantity' => 5,
        ]);

        $stock = Stock::where('product_id', $product->id)->first();
        $this->assertEquals(0.5, (float) $stock->balance);
    }
}
