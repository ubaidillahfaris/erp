<?php

namespace Tests\Feature;

use App\Models\Price;
use App\Models\Produk;
use App\Models\Sale;
use App\Models\Satuan;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesProcessTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_process_sale_and_automate_everything()
    {
        $user = User::factory()->superadmin()->create();
        $this->actingAs($user);

        $satuan = Satuan::create(['nama' => 'pcs', 'simbol' => 'pcs']);
        $produk = Produk::create([
            'nama' => 'Kopi Kapal Api',
            'sku' => 'KKOPI-001',
            'satuan_id' => $satuan->id,
            'is_active' => true,
        ]);

        // Setup Price
        Price::create([
            'produk_id' => $produk->id,
            'satuan_id' => $satuan->id,
            'purchase_price' => 1000,
            'retail_price' => 2500,
            'is_current' => true,
        ]);

        // Setup Initial Stock
        Stock::create([
            'produk_id' => $produk->id,
            'balance' => 100,
            'last_satuan_id' => $satuan->id,
        ]);

        $payload = [
            'tanggal' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'received_amount' => 15000,
            'change_amount' => 2500,
            'items' => [
                [
                    'produk_id' => $produk->id,
                    'satuan_id' => $satuan->id,
                    'qty' => 5,
                    'price' => 2500,
                    'cost' => 1000,
                ],
            ],
        ];

        $response = $this->post(route('pos.store'), $payload);
        $response->assertRedirect(route('pos.index'));

        // 1. Assert Sale created
        $this->assertDatabaseHas('sales', [
            'total_amount' => 12500,
            'received_amount' => 15000,
            'change_amount' => 2500,
            'payment_method' => 'cash',
        ]);

        $sale = Sale::first();
        $this->assertCount(1, $sale->items);

        // 2. Assert Stock deducted (100 - 5 = 95)
        $stock = Stock::where('produk_id', $produk->id)->first();
        $this->assertEquals(95, (float) $stock->balance);

        // 3. Assert Journal entries (Revenue & COGS)
        // Revenue: Debit 12500 (Penjualan)
        $this->assertDatabaseHas('journals', [
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'category' => 'penjualan',
            'type' => 'debit',
            'amount' => 12500,
        ]);

        // COGS: Kredit 5000 (HPP)
        $this->assertDatabaseHas('journals', [
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'category' => 'hpp',
            'type' => 'kredit',
            'amount' => 5000,
        ]);
    }
}
