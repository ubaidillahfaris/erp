<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Price;
use App\Models\Produk;
use App\Models\Satuan;
use App\Models\Stock;
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
        $this->pcs = Satuan::create(['nama' => 'pcs', 'simbol' => 'pcs']);
        $this->gram = Satuan::create(['nama' => 'gram', 'simbol' => 'g']);
    }

    public function test_warung_kopi_scenario_deducts_only_ingredients()
    {
        // Kopi Cangkir: track_stock=false, auto_deduct_on_sale=true
        $coffee = Produk::create(['nama' => 'Kopi Bubuk', 'sku' => 'RM-COFFEE', 'satuan_id' => $this->gram->id, 'type' => 'raw_material']);
        Stock::create(['produk_id' => $coffee->id, 'balance' => 1000, 'last_satuan_id' => $this->gram->id]);

        $kopiCangkir = Produk::create([
            'nama' => 'Kopi Cangkir',
            'sku' => 'FG-KOPI',
            'satuan_id' => $this->pcs->id,
            'type' => 'finished_good',
            'track_stock' => false,
        ]);
        Stock::create(['produk_id' => $kopiCangkir->id, 'balance' => 0, 'last_satuan_id' => $this->pcs->id]);
        Price::create(['produk_id' => $kopiCangkir->id, 'satuan_id' => $this->pcs->id, 'retail_price' => 5000, 'purchase_price' => 2000, 'is_current' => true]);

        $bom = Bom::create(['produk_id' => $kopiCangkir->id, 'nama' => 'Resep', 'is_active' => true, 'auto_deduct_on_sale' => true]);
        $bom->items()->create(['produk_id' => $coffee->id, 'satuan_id' => $this->gram->id, 'jumlah' => 10]);

        $this->post(route('pos.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'items' => [['produk_id' => $kopiCangkir->id, 'satuan_id' => $this->pcs->id, 'qty' => 1, 'price' => 5000, 'cost' => 2000]],
        ]);

        // Assert Ingredient deducted
        $this->assertEquals(990, (float) Stock::where('produk_id', $coffee->id)->first()->balance);
        // Assert FG stock NOT deducted (remains 0)
        $this->assertEquals(0, (float) Stock::where('produk_id', $kopiCangkir->id)->first()->balance);
    }

    public function test_pabrik_mie_scenario_deducts_only_finished_good()
    {
        // Mie Telur: track_stock=true, auto_deduct_on_sale=false
        $flour = Produk::create(['nama' => 'Terigu', 'sku' => 'RM-FLOUR', 'satuan_id' => $this->gram->id, 'type' => 'raw_material']);
        Stock::create(['produk_id' => $flour->id, 'balance' => 1000, 'last_satuan_id' => $this->gram->id]);

        $mieTelur = Produk::create([
            'nama' => 'Mie Telur',
            'sku' => 'FG-MIE',
            'satuan_id' => $this->gram->id,
            'type' => 'finished_good',
            'track_stock' => true,
        ]);
        Stock::create(['produk_id' => $mieTelur->id, 'balance' => 50, 'last_satuan_id' => $this->gram->id]); // Already produced 50g
        Price::create(['produk_id' => $mieTelur->id, 'satuan_id' => $this->gram->id, 'retail_price' => 10000, 'purchase_price' => 4000, 'is_current' => true]);

        $bom = Bom::create(['produk_id' => $mieTelur->id, 'nama' => 'Resep Mie', 'is_active' => true, 'auto_deduct_on_sale' => false]);
        $bom->items()->create(['produk_id' => $flour->id, 'satuan_id' => $this->gram->id, 'jumlah' => 0.8]); // 0.8g flour for 1g mie

        $this->post(route('pos.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'items' => [['produk_id' => $mieTelur->id, 'satuan_id' => $this->gram->id, 'qty' => 10, 'price' => 10000, 'cost' => 4000]],
        ]);

        // Assert Ingredient NOT deducted (remains 1000)
        $this->assertEquals(1000, (float) Stock::where('produk_id', $flour->id)->first()->balance);
        // Assert FG stock deducted (50 - 10 = 40)
        $this->assertEquals(40, (float) Stock::where('produk_id', $mieTelur->id)->first()->balance);
    }
}
