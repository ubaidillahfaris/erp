<?php

namespace Tests\Feature;

use App\Actions\RecordStockMovement;
use App\Models\Produk;
use App\Models\Restock;
use App\Models\Satuan;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_save_stock_opname_as_draft(): void
    {
        $user = User::factory()->superadmin()->create();
        $satuan = Satuan::factory()->create();
        $produk = Produk::factory()->create(['satuan_id' => $satuan->id]);

        $response = $this->actingAs($user)->post(route('stock-opname.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'keterangan' => 'Opname Draft',
            'status' => 'draft',
            'items' => [
                [
                    'produk_id' => $produk->id,
                    'satuan_id' => $satuan->id,
                    'system_qty' => 10,
                    'physical_qty' => 8,
                ],
            ],
        ]);

        $response->assertRedirect(route('stock-opname.index'));
        $this->assertDatabaseHas('stock_opnames', ['status' => 'draft']);

        // Stock should NOT change for draft
        $stock = Stock::where('produk_id', $produk->id)->first();
        $this->assertEquals(0, (float) ($stock->balance ?? 0));
    }

    public function test_completing_stock_opname_creates_adjustments(): void
    {
        $user = User::factory()->superadmin()->create();
        $satuan = Satuan::factory()->create();
        $produk = Produk::factory()->create(['satuan_id' => $satuan->id]);

        // Setup initial stock
        app(RecordStockMovement::class)->handle([
            'produk_id' => $produk->id,
            'satuan_id' => $satuan->id,
            'type' => 'in',
            'jumlah' => 10,
            'keterangan' => 'Initial',
        ]);

        $this->assertEquals(10, (float) Stock::where('produk_id', $produk->id)->first()->balance);

        $response = $this->actingAs($user)->post(route('stock-opname.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'completed',
            'items' => [
                [
                    'produk_id' => $produk->id,
                    'satuan_id' => $satuan->id,
                    'system_qty' => 10,
                    'physical_qty' => 15, // Found more physically
                ],
            ],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('stock_opnames', ['status' => 'completed']);
        $this->assertDatabaseHas('stock_opname_items', [
            'produk_id' => $produk->id,
            'physical_qty' => 15,
            'system_qty' => 10,
        ]);

        // Stock should change to 15
        $stock = Stock::where('produk_id', $produk->id)->first();
        $this->assertNotNull($stock, 'Stock record should exist');
        $this->assertEquals(15, (float) $stock->balance);

        // Verify adjustment movement exists
        $this->assertDatabaseHas('stock_movements', [
            'produk_id' => $produk->id,
            'type' => 'in',
            'jumlah' => 5,
            'reference_type' => 'stock_opname',
        ]);
    }

    public function test_can_settle_restock_debt(): void
    {
        $user = User::factory()->superadmin()->create();
        $restock = Restock::create([
            'tanggal' => now(),
            'total_biaya' => 100000,
            'total_bayar' => 0,
            'status_pembayaran' => 'hutang',
        ]);

        $response = $this->actingAs($user)->post(route('restock.settle', $restock));

        $response->assertRedirect();
        $restock->refresh();
        $this->assertEquals('lunas', $restock->status_pembayaran);
        $this->assertEquals(100000, $restock->total_bayar);
    }
}
