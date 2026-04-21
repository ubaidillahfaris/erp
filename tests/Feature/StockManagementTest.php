<?php

namespace Tests\Feature;

use App\Actions\RecordStockMovement;
use App\Models\Produk;
use App\Models\Restock;
use App\Models\Satuan;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Satuan $satuan;

    protected \App\Models\Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed required COA for restock journaling
        \App\Models\Account::create(['code' => '1101', 'name' => 'Kas', 'type' => 'asset', 'balance_type' => 'debit']);
        \App\Models\Account::create(['code' => '1301', 'name' => 'Persediaan Materi', 'type' => 'asset', 'balance_type' => 'debit']);
        \App\Models\Account::create(['code' => '2101', 'name' => 'Hutang', 'type' => 'liability', 'balance_type' => 'credit']);

        $this->user = User::factory()->superadmin()->create();
        $this->actingAs($this->user);

        $this->satuan = Satuan::create(['nama' => 'Pcs', 'simbol' => 'pcs']);
        $this->vendor = \App\Models\Vendor::factory()->create();
    }

    public function test_restock_creates_stock_movement_and_updates_balance()
    {
        $produk = Produk::factory()->create(['satuan_id' => $this->satuan->id]);

        $response = $this->post(route('restock.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'vendor_id' => $this->vendor->id,
            'status_pembayaran' => 'lunas',
            'total_bayar' => 10000,
            'items' => [
                [
                    'produk_id' => $produk->id,
                    'satuan_id' => $this->satuan->id,
                    'jumlah' => 10,
                    'harga_satuan' => 1000,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('stock_movements', [
            'produk_id' => $produk->id,
            'type' => 'in',
            'jumlah' => 10,
            'reference_type' => 'restock',
        ]);

        $this->assertDatabaseHas('stocks', [
            'produk_id' => $produk->id,
            'balance' => 10.0000,
        ]);
    }

    public function test_manual_adjustment_updates_stock()
    {
        $produk = Produk::factory()->create(['satuan_id' => $this->satuan->id]);

        $response = $this->post(route('stock.adjustment'), [
            'produk_id' => $produk->id,
            'satuan_id' => $this->satuan->id,
            'type' => 'in',
            'jumlah' => 50,
            'keterangan' => 'Initial stock opname',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('stocks', [
            'produk_id' => $produk->id,
            'balance' => 50.0000,
        ]);

        // Adjustment out
        $this->post(route('stock.adjustment'), [
            'produk_id' => $produk->id,
            'satuan_id' => $this->satuan->id,
            'type' => 'out',
            'jumlah' => 10,
            'keterangan' => 'Waste',
        ]);

        $this->assertEquals(40, (float) Stock::where('produk_id', $produk->id)->first()->balance);

        // Test Zero Adjustment
        $this->post(route('stock.adjustment'), [
            'produk_id' => $produk->id,
            'satuan_id' => $this->satuan->id,
            'type' => 'in',
            'jumlah' => 0,
            'keterangan' => 'Zero check',
        ]);
        $this->assertEquals(40, (float) Stock::where('produk_id', $produk->id)->first()->balance);
    }

    public function test_deleting_restock_removes_movements_and_reverts_stock()
    {
        $produk = Produk::factory()->create(['satuan_id' => $this->satuan->id]);

        $restock = Restock::create([
            'tanggal' => now(),
            'status_pembayaran' => 'lunas',
            'total_biaya' => 1000,
            'vendor_id' => $this->vendor->id,
        ]);

        app(RecordStockMovement::class)->handle([
            'produk_id' => $produk->id,
            'satuan_id' => $this->satuan->id,
            'type' => 'in',
            'jumlah' => 100,
            'reference_type' => 'restock',
            'reference_id' => $restock->id,
        ]);

        $this->assertEquals(100, (float) $produk->stock->balance);

        // Deleting restock should delete the movements via observer
        $restock->delete();

        $this->assertEquals(0, (float) Stock::where('produk_id', $produk->id)->first()->balance);
        $this->assertEquals(0, StockMovement::count());
    }
}
