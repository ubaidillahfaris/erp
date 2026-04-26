<?php

namespace Tests\Feature\Sprint1;

use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the permission first
        Permission::create(['name' => 'manage products']);

        // Ensure we have a user for authentication if needed
        $this->user = User::factory()->create();

        // Give permission to manage products
        $this->user->givePermissionTo('manage products');
    }

    public function test_a_product_can_be_soft_deleted()
    {
        $produk = Produk::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('produk.destroy', $produk));

        $response->assertRedirect(route('produk.index'));

        // Assert it's deleted from active listing
        $this->assertDatabaseMissing('produks', [
            'id' => $produk->id,
            'deleted_at' => null,
        ]);

        // Assert it still exists in the database
        $this->assertDatabaseHas('produks', [
            'id' => $produk->id,
        ]);

        $this->assertNotNull(Produk::withTrashed()->find($produk->id)->deleted_at);
    }

    public function test_bulk_delete_performs_soft_deletes()
    {
        $produks = Produk::factory()->count(3)->create();
        $ids = $produks->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->delete(route('produk.bulk-destroy', [
                'ids' => $ids,
            ]));

        $response->assertRedirect(route('produk.index'));

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('produks', [
                'id' => $id,
                'deleted_at' => null,
            ]);
            $this->assertNotNull(Produk::withTrashed()->find($id)->deleted_at);
        }
    }
}
