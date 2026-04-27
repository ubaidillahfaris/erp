<?php

namespace Tests\Feature;

use App\Models\Price;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RestockAutoSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_restock_create_page_receives_product_id_prop(): void
    {
        $user = User::factory()->superadmin()->create();
        $unit = Unit::factory()->create();
        $product = Product::factory()->create([
            'type' => 'raw_material',
            'unit_id' => $unit->id,
        ]);
        Price::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'purchase_price' => 5000,
            'retail_price' => 6000,
            'is_current' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('restock.create', ['product_id' => $product->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('restock/Create')
            ->has('productId')
            ->where('productId', (string) $product->id)
            ->has('bahanBakus.0.current_price')
        );
    }

    public function test_restock_create_page_has_null_product_id_prop_by_default(): void
    {
        $user = User::factory()->superadmin()->create();

        $response = $this->actingAs($user)
            ->get(route('restock.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('restock/Create')
            ->where('productId', null)
        );
    }
}
