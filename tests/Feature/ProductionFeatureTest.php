<?php

namespace Tests\Feature;

use App\Actions\RecalculateHpp;
use App\Models\Bom;
use App\Models\Production;
use App\Models\Produk;
use App\Models\Satuan;
use App\Models\SatuanConversion;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Satuan $kg;

    private Satuan $gr;

    private Satuan $liter;

    private Satuan $ml;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->superadmin()->create();

        // Setup base units
        $this->kg = Satuan::create(['nama' => 'Kilogram', 'simbol' => 'kg', 'is_base' => true]);
        $this->gr = Satuan::create(['nama' => 'Gram', 'simbol' => 'gr', 'is_base' => false]);
        $this->liter = Satuan::create(['nama' => 'Liter', 'simbol' => 'L', 'is_base' => true]);
        $this->ml = Satuan::create(['nama' => 'Mililiter', 'simbol' => 'ml', 'is_base' => false]);

        // Setup conversions
        SatuanConversion::create(['satuan_id' => $this->kg->id, 'to_satuan_id' => $this->gr->id, 'rasio' => 1000]);
        SatuanConversion::create(['satuan_id' => $this->liter->id, 'to_satuan_id' => $this->ml->id, 'rasio' => 1000]);
        // 1 ml air = 1 gr
        SatuanConversion::create(['satuan_id' => $this->ml->id, 'to_satuan_id' => $this->gr->id, 'rasio' => 1]);
    }

    public function test_can_complete_production_with_valid_yield()
    {
        // 1. Setup ingredients with prices
        $kopiBubuk = Produk::create(['nama' => 'Kopi Bubuk', 'type' => 'raw_material', 'satuan_id' => $this->gr->id, 'sku' => '123']);
        $kopiBubuk->currentPrice()->create(['purchase_price' => 150, 'retail_price' => 0, 'satuan_id' => $this->gr->id]); // 150/gr
        Stock::create(['produk_id' => $kopiBubuk->id, 'balance' => 1000, 'last_satuan_id' => $this->gr->id]);

        $air = Produk::create(['nama' => 'Air Mineral', 'type' => 'raw_material', 'satuan_id' => $this->ml->id, 'sku' => '124']);
        $air->currentPrice()->create(['purchase_price' => 2, 'retail_price' => 0, 'satuan_id' => $this->ml->id]); // 2/ml
        Stock::create(['produk_id' => $air->id, 'balance' => 2000, 'last_satuan_id' => $this->ml->id]);

        // 2. Setup Finished Good
        $baseKopi = Produk::create(['nama' => 'Base Kopi', 'type' => 'intermediate_good', 'satuan_id' => $this->liter->id, 'sku' => '125']);

        // 3. Setup BOM (requires 100gr kopi, 500ml air, expecting 400ml yield representing 0.4L)
        // Cost: (100 * 150) = 15,000 + (500 * 2) = 1,000 => 16,000 total
        // Expected Yield = 0.4 Liter. Theoretical HPP per Liter = 16,000 / 0.4 = 40,000.
        $bom = Bom::create(['produk_id' => $baseKopi->id, 'expected_yield' => 0.4, 'nama' => 'BOM Base Kopi']);
        $bom->items()->create(['produk_id' => $kopiBubuk->id, 'satuan_id' => $this->gr->id, 'jumlah' => 100]);
        $bom->items()->create(['produk_id' => $air->id, 'satuan_id' => $this->ml->id, 'jumlah' => 500]);

        // Ensure price is calculated
        app(RecalculateHpp::class)->handle($baseKopi);

        // Start production
        $response = $this->actingAs($this->user)->post(route('production.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'bom_id' => $bom->id,
            'produk_id' => $baseKopi->id,
            'target_yield' => 0.4,
            'items' => [
                ['produk_id' => $kopiBubuk->id, 'satuan_id' => $this->gr->id, 'planned_qty' => 100],
                ['produk_id' => $air->id, 'satuan_id' => $this->ml->id, 'planned_qty' => 500],
            ],
        ]);

        $production = Production::first();

        // Complete production using 415ml total actual yield = 0.415 L
        // And we accidentally used 110gr kopi instead of 100gr
        $completeResponse = $this->actingAs($this->user)->put(route('production.update', $production->id), [
            'actual_yield' => 0.415,
            'items' => [
                ['id' => $production->items[0]->id, 'produk_id' => $kopiBubuk->id, 'satuan_id' => $this->gr->id, 'actual_qty' => 110],
                ['id' => $production->items[1]->id, 'produk_id' => $air->id, 'satuan_id' => $this->ml->id, 'actual_qty' => 500],
            ],
        ]);

        $completeResponse->assertRedirect(route('production.index'));

        // Refresh instances
        $production->refresh();
        $this->assertEquals('completed', $production->status);

        // Assert Stock Deducted
        $kopiStock = Stock::where('produk_id', $kopiBubuk->id)->first();
        $this->assertEquals(1000 - 110, (float) $kopiStock->balance); // Actually deducted 110

        $baseKopiStock = Stock::where('produk_id', $baseKopi->id)->first();
        $this->assertEquals(0.415, (float) $baseKopiStock->balance);

        // Assert Cost is correct
        // 110gr * 150 = 16,500
        // 500ml * 2 = 1,000
        // Total Cost = 17,500
        $this->assertEquals(17500, $production->total_cost);
    }
}
