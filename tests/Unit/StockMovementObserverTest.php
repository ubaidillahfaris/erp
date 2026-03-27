<?php

namespace Tests\Unit;

use App\Models\Produk;
use App\Models\Satuan;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\SatuanConversion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementObserverTest extends TestCase
{
    use RefreshDatabase;

    private Satuan $pcs, $box;
    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pcs = Satuan::create(['nama' => 'Pieces', 'simbol' => 'pcs']);
        $this->box = Satuan::create(['nama' => 'Box', 'simbol' => 'box']);
        
        // 1 box = 10 pcs
        SatuanConversion::create([
            'satuan_id' => $this->box->id,
            'to_satuan_id' => $this->pcs->id,
            'rasio' => 10
        ]);

        // Product base unit is PCS
        $this->produk = Produk::factory()->create(['satuan_id' => $this->pcs->id]);
    }

    public function test_movement_created_updates_balance_same_unit(): void
    {
        StockMovement::create([
            'produk_id' => $this->produk->id,
            'satuan_id' => $this->pcs->id,
            'type' => 'in',
            'jumlah' => 50,
        ]);

        $stock = Stock::where('produk_id', $this->produk->id)->first();
        $this->assertEquals(50.0, (float) $stock->balance);
    }

    public function test_movement_created_updates_balance_with_conversion(): void
    {
        // Add 2 boxes (should be 20 pcs)
        StockMovement::create([
            'produk_id' => $this->produk->id,
            'satuan_id' => $this->box->id,
            'type' => 'in',
            'jumlah' => 2,
        ]);

        $stock = Stock::where('produk_id', $this->produk->id)->first();
        $this->assertEquals(20.0, (float) $stock->balance);
    }

    public function test_movement_out_decreases_balance(): void
    {
        // Initial 100 pcs
        StockMovement::create([
            'produk_id' => $this->produk->id,
            'satuan_id' => $this->pcs->id,
            'type' => 'in',
            'jumlah' => 100,
        ]);

        // Out 1 box (10 pcs)
        StockMovement::create([
            'produk_id' => $this->produk->id,
            'satuan_id' => $this->box->id,
            'type' => 'out',
            'jumlah' => 1,
        ]);

        $stock = Stock::where('produk_id', $this->produk->id)->first();
        $this->assertEquals(90.0, (float) $stock->balance);
    }

    public function test_movement_deleted_reverts_balance(): void
    {
        $movement = StockMovement::create([
            'produk_id' => $this->produk->id,
            'satuan_id' => $this->pcs->id,
            'type' => 'in',
            'jumlah' => 100,
        ]);

        $this->assertEquals(100.0, (float) $this->produk->fresh()->stock->balance);

        $movement->delete();

        $this->assertEquals(0.0, (float) Stock::where('produk_id', $this->produk->id)->first()->balance);
    }

    public function test_inverse_conversion_handling(): void
    {
        // Product base unit is BOX
        $produkBox = Produk::factory()->create(['satuan_id' => $this->box->id]);

        // Add 20 pcs (should be 2 boxes)
        StockMovement::create([
            'produk_id' => $produkBox->id,
            'satuan_id' => $this->pcs->id,
            'type' => 'in',
            'jumlah' => 20,
        ]);

        $stock = Stock::where('produk_id', $produkBox->id)->first();
        $this->assertEquals(2.0, (float) $stock->balance);
    }
}
