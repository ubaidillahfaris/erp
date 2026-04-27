<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Price;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomSalesDeductionTest extends TestCase
{
    use RefreshDatabase;

    protected $pcs;

    protected $gram;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->superadmin()->create();
        $this->actingAs($this->user);
        $this->pcs = Unit::create(['name' => 'pcs', 'symbol' => 'pcs']);
        $this->gram = Unit::create(['name' => 'gram', 'symbol' => 'g']);
    }

    public function test_warung_kopi_scenario_deducts_only_ingredients()
    {
        // Kopi Cangkir: track_stock=false, auto_deduct_on_sale=true
        $coffee = Product::create(['name' => 'Kopi Bubuk', 'sku' => 'RM-COFFEE', 'unit_id' => $this->gram->id, 'type' => 'raw_material']);
        Stock::create(['product_id' => $coffee->id, 'balance' => 1000, 'last_unit_id' => $this->gram->id]);

        $kopiCangkir = Product::create([
            'name' => 'Kopi Cangkir',
            'sku' => 'FG-KOPI',
            'unit_id' => $this->pcs->id,
            'type' => 'finished_good',
            'track_stock' => false,
        ]);
        Stock::create(['product_id' => $kopiCangkir->id, 'balance' => 0, 'last_unit_id' => $this->pcs->id]);
        Price::create(['product_id' => $kopiCangkir->id, 'unit_id' => $this->pcs->id, 'retail_price' => 5000, 'purchase_price' => 2000, 'is_current' => true]);

        $bom = Bom::create(['product_id' => $kopiCangkir->id, 'name' => 'Resep', 'is_active' => true, 'auto_deduct_on_sale' => true]);
        $bom->items()->create(['product_id' => $coffee->id, 'unit_id' => $this->gram->id, 'quantity' => 10]);

        $this->post(route('pos.store'), [
            'date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'items' => [['product_id' => $kopiCangkir->id, 'unit_id' => $this->pcs->id, 'qty' => 1, 'price' => 5000, 'cost' => 2000]],
        ]);

        // Assert Ingredient deducted
        $this->assertEquals(990, (float) Stock::where('product_id', $coffee->id)->first()->balance);
        // Assert FG stock NOT deducted (remains 0)
        $this->assertEquals(0, (float) Stock::where('product_id', $kopiCangkir->id)->first()->balance);
    }

    public function test_pabrik_mie_scenario_deducts_only_finished_good()
    {
        // Mie Telur: track_stock=true, auto_deduct_on_sale=false
        $flour = Product::create(['name' => 'Terigu', 'sku' => 'RM-FLOUR', 'unit_id' => $this->gram->id, 'type' => 'raw_material']);
        Stock::create(['product_id' => $flour->id, 'balance' => 1000, 'last_unit_id' => $this->gram->id]);

        $mieTelur = Product::create([
            'name' => 'Mie Telur',
            'sku' => 'FG-MIE',
            'unit_id' => $this->gram->id,
            'type' => 'finished_good',
            'track_stock' => true,
        ]);
        Stock::create(['product_id' => $mieTelur->id, 'balance' => 50, 'last_unit_id' => $this->gram->id]); // Already produced 50g
        Price::create(['product_id' => $mieTelur->id, 'unit_id' => $this->gram->id, 'retail_price' => 10000, 'purchase_price' => 4000, 'is_current' => true]);

        $bom = Bom::create(['product_id' => $mieTelur->id, 'name' => 'Resep Mie', 'is_active' => true, 'auto_deduct_on_sale' => false]);
        $bom->items()->create(['product_id' => $flour->id, 'unit_id' => $this->gram->id, 'quantity' => 0.8]); // 0.8g flour for 1g mie

        $this->post(route('pos.store'), [
            'date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'items' => [['product_id' => $mieTelur->id, 'unit_id' => $this->gram->id, 'qty' => 10, 'price' => 10000, 'cost' => 4000]],
        ]);

        // Assert Ingredient NOT deducted (remains 1000)
        $this->assertEquals(1000, (float) Stock::where('product_id', $flour->id)->first()->balance);
        // Assert FG stock deducted (50 - 10 = 40)
        $this->assertEquals(40, (float) Stock::where('product_id', $mieTelur->id)->first()->balance);
    }
}
