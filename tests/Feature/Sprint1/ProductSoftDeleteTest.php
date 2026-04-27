<?php

namespace Tests\Feature\Sprint1;

use App\Models\Product;
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
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('product.destroy', $product));

        $response->assertRedirect(route('product.index'));

        // Assert it's deleted from active listing
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);

        // Assert it still exists in the database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);

        $this->assertNotNull(Product::withTrashed()->find($product->id)->deleted_at);
    }

    public function test_bulk_delete_performs_soft_deletes()
    {
        $products = Product::factory()->count(3)->create();
        $ids = $products->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->delete(route('product.bulk-destroy', [
                'ids' => $ids,
            ]));

        $response->assertRedirect(route('product.index'));

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('products', [
                'id' => $id,
                'deleted_at' => null,
            ]);
            $this->assertNotNull(Product::withTrashed()->find($id)->deleted_at);
        }
    }
}
