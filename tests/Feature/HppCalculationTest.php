<?php

namespace Tests\Feature;

use App\Actions\RecalculateHpp;
use App\Models\Bom;
use App\Models\Price;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HppCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->superadmin()->create());
    }

    public function test_hpp_calculates_correctly_for_simple_bom()
    {
        $kg = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg']);
        $gr = Unit::create(['name' => 'Gram', 'symbol' => 'gr']);

        // $kg->conversions()->create(['target_unit_id' => $gr->id, 'ratio' => 1000]);
        // Inverse for the test: $gr to $kg (1 gr = 0.001 kg)
        $gr->conversions()->create(['target_unit_id' => $kg->id, 'ratio' => 0.001]);

        // Raw Material: Tepung (10000 / kg)
        $tepung = Product::create([
            'name' => 'Tepung',
            'type' => 'raw_material',
            'unit_id' => $kg->id,
        ]);
        $tepung->prices()->create([
            'unit_id' => $kg->id,
            'purchase_price' => 10000,
            'is_current' => true,
        ]);

        // Finished Good: Roti (Uses 200gr Tepung)
        $roti = Product::create([
            'name' => 'Roti',
            'type' => 'finished_good',
            'unit_id' => Unit::create(['name' => 'Pcs', 'symbol' => 'pcs'])->id,
        ]);

        $bom = Bom::create(['product_id' => $roti->id, 'sku' => 'BOM-ROTI']);
        $bom->items()->create([
            'product_id' => $tepung->id,
            'unit_id' => $gr->id,
            'quantity' => 200, // 200gr = 0.2kg
        ]);

        // Trigger calculation
        app(RecalculateHpp::class)->handle($roti);

        // Expected HPP: 0.2 * 10000 = 2000
        $this->assertEquals(2000, (float) $roti->currentPrice->purchase_price);
    }

    public function test_hpp_updates_recursively_for_nested_bom()
    {
        $kg = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg']);

        // Raw: Tepung (10000 / kg)
        $tepung = Product::create(['name' => 'Tepung', 'type' => 'raw_material', 'unit_id' => $kg->id]);
        $tepung->prices()->create(['unit_id' => $kg->id, 'purchase_price' => 10000, 'is_current' => true]);

        // Semi-finished: Adonan (Uses 1kg Tepung)
        $adonan = Product::create(['name' => 'Adonan', 'type' => 'semi_finished', 'unit_id' => $kg->id]);
        $bomAdonan = Bom::create(['product_id' => $adonan->id, 'sku' => 'BOM-ADONAN']);
        $bomAdonan->items()->create(['product_id' => $tepung->id, 'unit_id' => $kg->id, 'quantity' => 1]);

        app(RecalculateHpp::class)->handle($adonan);
        $this->assertEquals(10000, (float) $adonan->currentPrice->purchase_price);

        // Finished: Roti (Uses 0.1kg Adonan)
        $roti = Product::create(['name' => 'Roti', 'type' => 'finished_good', 'unit_id' => Unit::create(['name' => 'Pcs', 'symbol' => 'pcs'])->id]);
        $bomRoti = Bom::create(['product_id' => $roti->id, 'sku' => 'BOM-ROTI']);
        $bomRoti->items()->create(['product_id' => $adonan->id, 'unit_id' => $kg->id, 'quantity' => 0.1]);

        app(RecalculateHpp::class)->handle($roti);
        $this->assertEquals(1000, (float) $roti->currentPrice->purchase_price);

        // ACT: Update Tepung Price to 20000 via a "Restock-like" trigger
        $tepung->prices()->where('is_current', true)->update(['is_current' => false]);
        $tepung->prices()->create(['unit_id' => $kg->id, 'purchase_price' => 20000, 'is_current' => true]);

        // Manually trigger handle on tepung to simulate cascade (usually triggered in RestockController)
        app(RecalculateHpp::class)->handle($tepung);

        // ASSERT: Adonan should be 20000, Roti should be 2000
        $this->assertEquals(20000, (float) $adonan->fresh()->currentPrice->purchase_price);
        $this->assertEquals(2000, (float) $roti->fresh()->currentPrice->purchase_price);
    }

    public function test_retail_price_is_preserved_during_hpp_recalculation()
    {
        $kg = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg']);

        // Raw: Tepung (10000 / kg)
        $tepung = Product::create(['name' => 'Tepung', 'type' => 'raw_material', 'unit_id' => $kg->id]);
        $tepung->prices()->create([
            'unit_id' => $kg->id,
            'purchase_price' => 10000,
            'retail_price' => 15000,
            'is_current' => true,
        ]);

        // Finished: Roti (Uses 1kg Tepung)
        $roti = Product::create(['name' => 'Roti', 'type' => 'finished_good', 'unit_id' => $kg->id]);
        $roti->prices()->create([
            'unit_id' => $kg->id,
            'purchase_price' => 5000,
            'retail_price' => 20000, // Pre-existing selling price
            'is_current' => true,
        ]);

        $bomRoti = Bom::create(['product_id' => $roti->id, 'sku' => 'BOM-ROTI']);
        $bomRoti->items()->create(['product_id' => $tepung->id, 'unit_id' => $kg->id, 'quantity' => 1]);

        // ACT: Trigger recalculation
        app(RecalculateHpp::class)->handle($roti);

        // ASSERT: purchase_price should be 10000, retail_price should STILL be 20000
        $rotiPrice = $roti->fresh()->currentPrice;
        $this->assertEquals(10000, (float) $rotiPrice->purchase_price);
        $this->assertEquals(20000, (float) $rotiPrice->retail_price);
    }
}
