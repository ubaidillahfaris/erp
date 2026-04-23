<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Models\Purchase;
use App\Models\Satuan;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('superadmin');
    }

    // ---------------------
    // HAPPY PATH TESTS
    // ---------------------

    public function test_admin_can_view_purchasing_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('purchasing.index'));
        $response->assertOk();
    }

    public function test_admin_can_create_a_draft_purchase(): void
    {
        $vendor = Vendor::factory()->create();
        $satuan = Satuan::factory()->create();
        $produk = Produk::factory()->create(['satuan_id' => $satuan->id]);

        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'no_invoice' => 'INV-001',
            'vendor_id' => $vendor->id,
            'tanggal' => now()->toDateString(),
            'transaction_type' => 'purchase',
            'keterangan' => 'Test purchase',
            'items' => [
                [
                    'produk_id' => $produk->id,
                    'satuan_id' => $satuan->id,
                    'jumlah' => 10,
                    'harga_satuan' => 5000,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchasing.index'));
        $this->assertDatabaseHas('purchases', [
            'no_invoice' => 'INV-001',
            'status' => 'draft',
            'transaction_type' => 'purchase',
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'produk_id' => $produk->id,
            'jumlah' => 10,
        ]);
    }

    public function test_finalizing_purchase_updates_stock_and_price_stats(): void
    {
        $vendor = Vendor::factory()->create();
        $satuan = Satuan::factory()->create();
        $produk = Produk::factory()->create(['satuan_id' => $satuan->id, 'track_stock' => true]);

        // Create a draft purchase
        $purchase = Purchase::create([
            'vendor_id' => $vendor->id,
            'tanggal' => now()->toDateString(),
            'transaction_type' => 'purchase',
            'status' => 'draft',
            'total_biaya' => 50000,
        ]);

        $purchase->items()->create([
            'produk_id' => $produk->id,
            'satuan_id' => $satuan->id,
            'jumlah' => 10,
            'harga_satuan' => 5000,
        ]);

        // Finalize
        $response = $this->actingAs($this->admin)
            ->post(route('purchasing.finalize', $purchase));

        $response->assertRedirect(route('purchasing.index'));

        // Assert purchase is finalized
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'finalized',
        ]);

        // Assert stock movement was recorded
        $this->assertDatabaseHas('stock_movements', [
            'produk_id' => $produk->id,
            'type' => 'in',
            'jumlah' => 10,
            'reference_type' => 'purchase',
        ]);

        // Assert price stats were updated
        $this->assertDatabaseHas('product_price_stats', [
            'produk_id' => $produk->id,
            'satuan_id' => $satuan->id,
            'avg_price' => 5000,
            'min_price' => 5000,
            'max_price' => 5000,
        ]);
    }

    public function test_gift_purchase_forces_price_to_zero_and_vendor_to_null(): void
    {
        $satuan = Satuan::factory()->create();
        $produk = Produk::factory()->create(['satuan_id' => $satuan->id]);
        $vendor = Vendor::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'tanggal' => now()->toDateString(),
            'transaction_type' => 'gift',
            'vendor_id' => $vendor->id, // Sent intentionally by TF
            'keterangan' => 'Bonus dari distributor',
            'items' => [
                [
                    'produk_id' => $produk->id,
                    'satuan_id' => $satuan->id,
                    'jumlah' => 5,
                    'harga_satuan' => 1000000, // Sent intentionally by TF
                ],
            ],
        ]);

        $response->assertRedirect(route('purchasing.index'));
        $this->assertDatabaseHas('purchases', [
            'transaction_type' => 'gift',
            'status' => 'draft',
            'vendor_id' => null, // Should be forced to null
            'total_biaya' => 0, // Should be computed as 0
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'jumlah' => 5,
            'harga_satuan' => 0, // Should be forced to 0
        ]);
    }

    public function test_admin_can_update_draft_purchase(): void
    {
        $vendor = Vendor::factory()->create();
        $purchase = Purchase::factory()->create(['vendor_id' => $vendor->id]);

        $satuan = Satuan::factory()->create();
        $produk = Produk::factory()->create(['satuan_id' => $satuan->id]);

        $response = $this->actingAs($this->admin)->put(route('purchasing.update', $purchase), [
            'no_invoice' => 'INV-REVISED',
            'vendor_id' => $vendor->id,
            'tanggal' => now()->toDateString(),
            'transaction_type' => 'purchase',
            'keterangan' => 'Revised via edit',
            'items' => [
                [
                    'produk_id' => $produk->id,
                    'satuan_id' => $satuan->id,
                    'jumlah' => 20, // Changed qty
                    'harga_satuan' => 10000,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchasing.show', $purchase));
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'no_invoice' => 'INV-REVISED',
            'keterangan' => 'Revised via edit',
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'jumlah' => 20,
        ]);
    }

    public function test_admin_cannot_update_finalized_purchase(): void
    {
        $vendor = Vendor::factory()->create();
        $purchase = Purchase::factory()->finalized()->create(['vendor_id' => $vendor->id]);

        $satuan = Satuan::factory()->create();
        $produk = Produk::factory()->create(['satuan_id' => $satuan->id]);

        $response = $this->actingAs($this->admin)->put(route('purchasing.update', $purchase), [
            'vendor_id' => $vendor->id,
            'tanggal' => now()->toDateString(),
            'transaction_type' => 'purchase',
            'items' => [
                [
                    'produk_id' => $produk->id,
                    'satuan_id' => $satuan->id,
                    'jumlah' => 10,
                    'harga_satuan' => 5000,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchasing.index'));
        $response->assertSessionHas('error');
    }

    // ---------------------
    // FAILURE PATH TESTS
    // ---------------------

    public function test_purchase_type_requires_vendor(): void
    {
        $satuan = Satuan::factory()->create();
        $produk = Produk::factory()->create(['satuan_id' => $satuan->id]);

        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'tanggal' => now()->toDateString(),
            'transaction_type' => 'purchase',
            'vendor_id' => null, // no vendor
            'items' => [
                ['produk_id' => $produk->id, 'satuan_id' => $satuan->id, 'jumlah' => 1, 'harga_satuan' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('vendor_id');
    }

    public function test_cannot_delete_finalized_purchase(): void
    {
        $purchase = Purchase::factory()->create(['status' => 'finalized']);

        $response = $this->actingAs($this->admin)->delete(route('purchasing.destroy', $purchase));

        $response->assertRedirect();
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id]);
    }

    public function test_cannot_finalize_already_finalized_purchase(): void
    {
        $purchase = Purchase::factory()->create(['status' => 'finalized']);

        $response = $this->actingAs($this->admin)
            ->post(route('purchasing.finalize', $purchase));

        $response->assertStatus(500); // Should throw exception
    }
}
