<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Models\Purchase;
use App\Models\Satuan;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug()
    {
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $vendor = Vendor::factory()->create();
        $purchase = Purchase::factory()->create(['vendor_id' => $vendor->id]);

        $satuan = Satuan::factory()->create();
        $produk = Produk::factory()->create(['satuan_id' => $satuan->id]);

        $item = [
            'produk_id' => $produk->id,
            'satuan_id' => $satuan->id,
            'jumlah' => 20,
            'harga_satuan' => 10000,
        ];

        dump('Before create: '.$purchase->id);

        $purchase->update([
            'no_invoice' => 'INV-REVISED',
        ]);

        dump('After update: '.$purchase->id);

        $purchase->items()->create($item);

        dump('After items create');
    }
}
