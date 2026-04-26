<?php

namespace Tests\Feature\Sprint3;

use App\Actions\RecordStockMovement;
use App\Models\Produk;
use App\Models\Satuan;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyLockTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_it_throws_exception_and_rolls_back_if_stock_insufficient()
    {
        $satuan = Satuan::factory()->create();
        $produk = Produk::factory()->create([
            'track_stock' => true,
            'satuan_id' => $satuan->id,
        ]);

        // Setup initial stock: 5 units
        Stock::create([
            'produk_id' => $produk->id,
            'balance' => 5,
            'last_satuan_id' => $satuan->id,
        ]);

        $this->assertEquals(5, $produk->fresh()->stock->balance);

        try {
            DB::transaction(function () use ($produk, $satuan) {
                // Try to deduct 10 units (more than 5)
                app(RecordStockMovement::class)->handle([
                    'produk_id' => $produk->id,
                    'satuan_id' => $satuan->id,
                    'type' => 'out',
                    'jumlah' => 10,
                ]);
            });
            $this->fail('Should have thrown RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('tidak mencukupi', $e->getMessage());
        }

        // Verify stock is still 5 (rolled back)
        $this->assertEquals(5, $produk->fresh()->stock->balance);

        // Verify no movement record was persisted
        $this->assertEquals(0, StockMovement::count());
    }

    /** @test */
    public function test_it_enforces_locking_logic()
    {
        $satuan = Satuan::factory()->create();
        $produk = Produk::factory()->create([
            'satuan_id' => $satuan->id,
        ]);

        // First movement (IN) should create the stock record and lock it
        app(RecordStockMovement::class)->handle([
            'produk_id' => $produk->id,
            'satuan_id' => $satuan->id,
            'type' => 'in',
            'jumlah' => 100,
        ]);

        $this->assertEquals(100, $produk->fresh()->stock->balance);
    }
}
