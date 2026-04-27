<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class ProductOverheadTest extends TestCase
{
    use RefreshDatabase;

    protected Unit $unit;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->unit = Unit::create(['name' => 'PCS', 'symbol' => 'PCS']);
        $this->user = User::factory()->create();
    }

    /**
     * Test storing product with overhead_rate converts to cents in DB.
     */
    public function test_store_product_with_overhead_rate_converts_to_cents(): void
    {
        $response = $this->actingAs($this->user)
            ->withoutMiddleware(PermissionMiddleware::class)
            ->post(route('product.store'), [
                'sku' => 'SKU-OH-1',
                'name' => 'Product Jadi A',
                'type' => 'finished_good',
                'unit_id' => $this->unit->id,
                'min_stock' => 10,
                'overhead_rate' => 500.50, // Rp 500.50
            ]);

        $response->assertStatus(302);

        $product = Product::where('sku', 'SKU-OH-1')->first();
        $this->assertNotNull($product, 'Product should be created');
        $this->assertEquals(50050, $product->overhead_rate_per_unit);
    }

    /**
     * Test updating product overhead_rate updates cents correctly.
     */
    public function test_update_product_overhead_rate_updates_cents_correctly(): void
    {
        $product = Product::create([
            'sku' => 'SKU-OH-2',
            'name' => 'Product Jadi B',
            'type' => 'finished_good',
            'unit_id' => $this->unit->id,
            'min_stock' => 10,
            'overhead_rate_per_unit' => 10000, // Rp 100
        ]);

        $response = $this->actingAs($this->user)
            ->withoutMiddleware(PermissionMiddleware::class)
            ->put(route('product.update', $product), [
                'sku' => 'SKU-OH-2',
                'name' => 'Product Jadi B Updated',
                'type' => 'finished_good',
                'unit_id' => $this->unit->id,
                'min_stock' => 10,
                'is_active' => true,
                'overhead_rate' => 750.25, // Rp 750.25
            ]);

        $response->assertStatus(302);

        $this->assertEquals(75025, $product->fresh()->overhead_rate_per_unit);
    }

    /**
     * Test that overhead_rate is not required for non-finished goods.
     */
    public function test_product_type_bukan_finished_good_overhead_rate_nullable(): void
    {
        $response = $this->actingAs($this->user)
            ->withoutMiddleware(PermissionMiddleware::class)
            ->post(route('product.store'), [
                'sku' => 'SKU-RAW-1',
                'name' => 'Raw Materials A',
                'type' => 'raw_material',
                'unit_id' => $this->unit->id,
                'min_stock' => 10,
                'overhead_rate' => null,
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('products', ['sku' => 'SKU-RAW-1', 'overhead_rate_per_unit' => null]);
    }

    /**
     * Test that show method passes overhead_rate in Rupiah to Inertia.
     */
    public function test_show_passes_overhead_rate_as_rupiah(): void
    {
        $product = Product::create([
            'sku' => 'SKU-OH-3',
            'name' => 'Product Jadi C',
            'type' => 'finished_good',
            'unit_id' => $this->unit->id,
            'min_stock' => 10,
            'overhead_rate_per_unit' => 12345, // Rp 123.45
        ]);

        $response = $this->actingAs($this->user)
            ->withoutMiddleware(PermissionMiddleware::class)
            ->get(route('product.show', $product));

        $response->assertStatus(200);
        $this->assertEquals(123.45, $response->inertiaPage()['props']['overhead_rate']);
    }
}
