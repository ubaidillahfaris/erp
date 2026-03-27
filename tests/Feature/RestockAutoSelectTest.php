<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RestockAutoSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_restock_create_page_receives_produk_id_prop(): void
    {
        $user = User::factory()->create();
        $satuan = \App\Models\Satuan::factory()->create();
        $produk = Produk::factory()->create([
            'type' => 'raw_material',
            'satuan_id' => $satuan->id,
        ]);
        \App\Models\Price::create([
            'produk_id' => $produk->id,
            'satuan_id' => $satuan->id,
            'purchase_price' => 5000,
            'retail_price' => 6000,
            'is_current' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('restock.create', ['produk_id' => $produk->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('restock/Create')
            ->has('produkId')
            ->where('produkId', (string) $produk->id)
            ->has('bahanBakus.0.current_price')
        );
    }

    public function test_restock_create_page_has_null_produk_id_prop_by_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('restock.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('restock/Create')
            ->where('produkId', null)
        );
    }
}
