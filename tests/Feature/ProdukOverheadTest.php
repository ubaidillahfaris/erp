<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdukOverheadTest extends TestCase
{
    use RefreshDatabase;

    protected Satuan $satuan;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->satuan = Satuan::create(['nama' => 'PCS', 'simbol' => 'PCS']);
        $this->user = User::factory()->create();
    }

    /**
     * Test storing produk with overhead_rate converts to cents in DB.
     */
    public function test_store_produk_with_overhead_rate_converts_to_cents(): void
    {
        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class)
            ->post(route('produk.store'), [
                'sku' => 'SKU-OH-1',
                'nama' => 'Produk Jadi A',
                'type' => 'finished_good',
                'satuan_id' => $this->satuan->id,
                'stok_minimal' => 10,
                'overhead_rate' => 500.50, // Rp 500.50
            ]);

        $response->assertStatus(302);
        
        $produk = Produk::where('sku', 'SKU-OH-1')->first();
        $this->assertNotNull($produk, 'Produk should be created');
        $this->assertEquals(50050, $produk->overhead_rate_per_unit);
    }

    /**
     * Test updating produk overhead_rate updates cents correctly.
     */
    public function test_update_produk_overhead_rate_updates_cents_correctly(): void
    {
        $produk = Produk::create([
            'sku' => 'SKU-OH-2',
            'nama' => 'Produk Jadi B',
            'type' => 'finished_good',
            'satuan_id' => $this->satuan->id,
            'stok_minimal' => 10,
            'overhead_rate_per_unit' => 10000, // Rp 100
        ]);

        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class)
            ->put(route('produk.update', $produk), [
                'sku' => 'SKU-OH-2',
                'nama' => 'Produk Jadi B Updated',
                'type' => 'finished_good',
                'satuan_id' => $this->satuan->id,
                'stok_minimal' => 10,
                'is_active' => true,
                'overhead_rate' => 750.25, // Rp 750.25
            ]);

        $response->assertStatus(302);
        
        $this->assertEquals(75025, $produk->fresh()->overhead_rate_per_unit);
    }

    /**
     * Test that overhead_rate is not required for non-finished goods.
     */
    public function test_produk_type_bukan_finished_good_overhead_rate_nullable(): void
    {
        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class)
            ->post(route('produk.store'), [
                'sku' => 'SKU-RAW-1',
                'nama' => 'Bahan Baku A',
                'type' => 'raw_material',
                'satuan_id' => $this->satuan->id,
                'stok_minimal' => 10,
                'overhead_rate' => null,
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('produks', ['sku' => 'SKU-RAW-1', 'overhead_rate_per_unit' => null]);
    }

    /**
     * Test that show method passes overhead_rate in Rupiah to Inertia.
     */
    public function test_show_passes_overhead_rate_as_rupiah(): void
    {
        $produk = Produk::create([
            'sku' => 'SKU-OH-3',
            'nama' => 'Produk Jadi C',
            'type' => 'finished_good',
            'satuan_id' => $this->satuan->id,
            'stok_minimal' => 10,
            'overhead_rate_per_unit' => 12345, // Rp 123.45
        ]);

        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class)
            ->get(route('produk.show', $produk));

        $response->assertStatus(200);
        $this->assertEquals(123.45, $response->inertiaPage()['props']['overhead_rate']);
    }
}
