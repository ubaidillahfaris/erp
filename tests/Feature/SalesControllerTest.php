<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Produk;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class SalesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Satuan $satuan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->superadmin()->create();
        $this->actingAs($this->user);

        $this->satuan = Satuan::create(['nama' => 'PCS', 'simbol' => 'pcs']);

        // Required Accounts for Storno
        Account::create(['code' => '1101', 'name' => 'Cash', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1102', 'name' => 'Receivable', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1302', 'name' => 'Finished Goods', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '4101', 'name' => 'Sales', 'type' => 'income', 'balance_type' => 'credit']);
        Account::create(['code' => '5101', 'name' => 'COGS', 'type' => 'expense', 'balance_type' => 'debit']);
    }

    public function test_index_displays_sales_list()
    {
        Sale::create([
            'invoice_number' => 'IV-001',
            'tanggal' => now(),
            'total_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $this->get(route('sales.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Index')
                ->has('sales.data', 1)
            );
    }

    public function test_index_filters_by_search()
    {
        Sale::create([
            'invoice_number' => 'IV-ALPHA',
            'tanggal' => now(),
            'total_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        Sale::create([
            'invoice_number' => 'IV-BETA',
            'tanggal' => now(),
            'total_amount' => 20000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $this->get(route('sales.index', ['search' => 'ALPHA']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('sales.data', 1)
                ->where('sales.data.0.invoice_number', 'IV-ALPHA')
            );
    }

    public function test_show_displays_sale_details()
    {
        $sale = Sale::create([
            'invoice_number' => 'IV-DETAIL',
            'tanggal' => now(),
            'total_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $this->get(route('sales.show', $sale))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Show')
                ->where('sale.invoice_number', 'IV-DETAIL')
            );
    }

    public function test_storno_sale_requires_permission()
    {
        $sale = Sale::create([
            'invoice_number' => 'IV-VOID',
            'tanggal' => now(),
            'total_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        // Create a regular user without permissions
        $regularUser = User::factory()->create();
        $this->actingAs($regularUser);

        $this->post(route('sales.void', $sale), ['reason' => 'Mistake'])
            ->assertForbidden();
    }

    public function test_storno_sale_success_with_permission()
    {
        // Ensure permission exists and is assigned to superadmin (which we use in setUp)
        Permission::firstOrCreate(['name' => 'void sales']);
        
        $produk = Produk::create([
            'sku' => 'SKU-001',
            'nama' => 'Produk 1',
            'satuan_id' => $this->satuan->id,
            'track_stock' => false,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'IV-STORNO',
            'tanggal' => now(),
            'total_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
            'cogs_amount' => 5000,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'produk_id' => $produk->id,
            'satuan_id' => $this->satuan->id,
            'qty' => 1,
            'price' => 10000,
            'cost' => 5000,
            'subtotal' => 10000,
        ]);

        $this->post(route('sales.void', $sale), ['reason' => 'Wrong item chosen'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($sale->fresh()->storno_at);
        $this->assertEquals('Wrong item chosen', $sale->fresh()->storno_reason);
    }
}
