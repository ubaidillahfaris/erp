<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Models\Satuan;
use App\Models\SatuanConversion;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_specific_conversion_takes_precedence(): void
    {
        $pack = Satuan::factory()->create(['nama' => 'Pack', 'simbol' => 'pack']);
        $pcs = Satuan::factory()->create(['nama' => 'Pcs', 'simbol' => 'pcs']);
        $renteng = Satuan::factory()->create(['nama' => 'Renteng', 'simbol' => 'rtg']);

        // Global conversion: 1 Renteng = 10 Pcs
        SatuanConversion::create([
            'satuan_id' => $renteng->id,
            'to_satuan_id' => $pcs->id,
            'rasio' => 10,
            'produk_id' => null,
        ]);

        $produk = Produk::factory()->create([
            'nama' => 'Kapal Api Krim Kafe',
            'satuan_id' => $pack->id,
        ]);

        // Product specific: 1 Pack = 50 Pcs
        SatuanConversion::create([
            'satuan_id' => $pack->id,
            'to_satuan_id' => $pcs->id,
            'rasio' => 50,
            'produk_id' => $produk->id,
        ]);

        // Record stock movement: 5 pcs IN
        // Ratio should be 1 Pack = 50 Pcs, so 5 pcs = 5/50 = 0.1 Pack
        StockMovement::create([
            'produk_id' => $produk->id,
            'satuan_id' => $pcs->id,
            'type' => 'in',
            'jumlah' => 5,
        ]);

        $stock = Stock::where('produk_id', $produk->id)->first();
        $this->assertEquals(0.1, (float) $stock->balance);
    }

    public function test_fallback_to_global_conversion(): void
    {
        $pack = Satuan::factory()->create(['nama' => 'Pack', 'simbol' => 'pack']);
        $pcs = Satuan::factory()->create(['nama' => 'Pcs', 'simbol' => 'pcs']);
        $renteng = Satuan::factory()->create(['nama' => 'Renteng', 'simbol' => 'rtg']);

        // Global conversion: 1 Renteng = 10 Pcs
        SatuanConversion::create([
            'satuan_id' => $renteng->id,
            'to_satuan_id' => $pcs->id,
            'rasio' => 10,
            'produk_id' => null,
        ]);

        $produk = Produk::factory()->create([
            'nama' => 'Some Other Product',
            'satuan_id' => $renteng->id,
        ]);

        // Record stock movement: 5 pcs IN
        // Falling back to global ratio: 1 Renteng = 10 Pcs, so 5 pcs = 5/10 = 0.5 Renteng
        StockMovement::create([
            'produk_id' => $produk->id,
            'satuan_id' => $pcs->id,
            'type' => 'in',
            'jumlah' => 5,
        ]);

        $stock = Stock::where('produk_id', $produk->id)->first();
        $this->assertEquals(0.5, (float) $stock->balance);
    }
}
