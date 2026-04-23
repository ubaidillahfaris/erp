<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Price;
use App\Models\Produk;
use App\Models\Restock;
use App\Models\Satuan;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceManagementTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed required COA for restock journaling
        Account::create(['code' => '1101', 'name' => 'Kas', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1301', 'name' => 'Persediaan Materi', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '2101', 'name' => 'Hutang', 'type' => 'liability', 'balance_type' => 'credit']);

        $this->vendor = Vendor::factory()->create();
    }

    public function test_purchase_price_is_automatically_tracked_on_restock(): void
    {
        $user = User::factory()->superadmin()->create();
        $satuan = Satuan::create(['nama' => 'Pcs', 'simbol' => 'pcs']);
        $produk = Produk::create([
            'sku' => 'P001',
            'nama' => 'Produk Test',
            'type' => 'raw_material',
            'satuan_id' => $satuan->id,
            'stok_minimal' => 5,
        ]);

        // 1. Initial restock
        $this->actingAs($user)->post(route('restock.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'vendor_id' => $this->vendor->id,
            'status_pembayaran' => 'lunas',
            'total_bayar' => 10000,
            'items' => [
                [
                    'produk_id' => $produk->id,
                    'satuan_id' => $satuan->id,
                    'jumlah' => 10,
                    'harga_satuan' => 1000,
                ],
            ],
        ]);

        $this->assertDatabaseHas('prices', [
            'produk_id' => $produk->id,
            'satuan_id' => $satuan->id,
            'purchase_price' => 1000.00,
            'is_current' => true,
        ]);

        // 2. Second restock with different price
        $this->actingAs($user)->post(route('restock.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'vendor_id' => $this->vendor->id,
            'status_pembayaran' => 'lunas',
            'total_bayar' => 12000,
            'items' => [
                [
                    'produk_id' => $produk->id,
                    'satuan_id' => $satuan->id,
                    'jumlah' => 10,
                    'harga_satuan' => 1200,
                ],
            ],
        ]);

        // Check that old price is no longer current
        $this->assertDatabaseHas('prices', [
            'purchase_price' => 1000.00,
            'is_current' => false,
        ]);

        // Check that new price is current
        $this->assertDatabaseHas('prices', [
            'purchase_price' => 1200.00,
            'is_current' => true,
        ]);
    }

    public function test_can_update_retail_price_for_product(): void
    {
        $user = User::factory()->superadmin()->create();
        $satuan = Satuan::create(['nama' => 'Pcs', 'simbol' => 'pcs']);
        $produk = Produk::create([
            'sku' => 'P001',
            'nama' => 'Produk Test',
            'type' => 'finished_good',
            'satuan_id' => $satuan->id,
            'stok_minimal' => 5,
            'is_active' => true,
        ]);

        // Create initial price record (e.g. from restock)
        Price::create([
            'produk_id' => $produk->id,
            'satuan_id' => $satuan->id,
            'purchase_price' => 1000,
            'retail_price' => 1500,
            'is_current' => true,
        ]);

        $response = $this->actingAs($user)->put(route('produk.update', $produk), [
            'sku' => 'P001',
            'nama' => 'Produk Test Updated',
            'type' => 'finished_good',
            'is_active' => true,
            'stok_minimal' => 5,
            'retail_price' => 2000,
            'wholesale_price' => 1800,
            'satuan_id' => $satuan->id,
        ]);

        $response->assertRedirect(route('produk.index'));

        $this->assertDatabaseHas('prices', [
            'produk_id' => $produk->id,
            'retail_price' => 2000.00,
            'wholesale_price' => 1800.00,
            'is_current' => true,
        ]);
    }

    public function test_can_create_product_with_initial_prices(): void
    {
        $user = User::factory()->superadmin()->create();
        $satuan = Satuan::create(['nama' => 'Pcs', 'simbol' => 'pcs']);

        $response = $this->actingAs($user)->post(route('produk.store'), [
            'sku' => 'PNEW',
            'nama' => 'Produk Baru',
            'type' => 'finished_good',
            'stok_minimal' => 10,
            'satuan_id' => $satuan->id,
            'retail_price' => 5000,
            'wholesale_price' => 4500,
        ]);

        $response->assertRedirect(route('produk.index'));

        $produk = Produk::where('sku', 'PNEW')->first();
        $this->assertNotNull($produk);

        $this->assertDatabaseHas('prices', [
            'produk_id' => $produk->id,
            'retail_price' => 5000.00,
            'wholesale_price' => 4500.00,
            'is_current' => true,
        ]);
    }

    public function test_can_update_restock_and_track_price(): void
    {
        $user = User::factory()->superadmin()->create();
        $satuan = Satuan::create(['nama' => 'Pcs', 'simbol' => 'pcs']);
        $produk = Produk::create([
            'sku' => 'PREST',
            'nama' => 'Restock Product',
            'type' => 'raw_material',
            'satuan_id' => $satuan->id,
            'stok_minimal' => 5,
        ]);

        // 1. Create restock
        $restock = Restock::create([
            'tanggal' => now()->format('Y-m-d'),
            'status_pembayaran' => 'lunas',
            'total_bayar' => 1000,
            'total_biaya' => 1000,
            'vendor_id' => $this->vendor->id,
        ]);
        $restock->items()->create([
            'produk_id' => $produk->id,
            'satuan_id' => $satuan->id,
            'jumlah' => 1,
            'harga_satuan' => 1000,
        ]);

        // 2. Update restock via controller
        $response = $this->actingAs($user)->put(route('restock.update', $restock), [
            'tanggal' => now()->format('Y-m-d'),
            'status_pembayaran' => 'lunas',
            'total_bayar' => 1500,
            'items' => [
                [
                    'produk_id' => $produk->id,
                    'satuan_id' => $satuan->id,
                    'jumlah' => 1,
                    'harga_satuan' => 1500,
                ],
            ],
        ]);

        $response->assertRedirect(route('restock.index'));

        // Should have new current price
        $this->assertDatabaseHas('prices', [
            'produk_id' => $produk->id,
            'purchase_price' => 1500.00,
            'is_current' => true,
        ]);
    }
}
