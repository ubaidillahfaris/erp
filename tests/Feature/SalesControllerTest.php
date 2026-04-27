<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->superadmin()->create();
        $this->actingAs($this->user);

        $this->unit = Unit::create(['name' => 'PCS', 'symbol' => 'pcs']);

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
            'date' => now(),
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
            'date' => now(),
            'total_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        Sale::create([
            'invoice_number' => 'IV-BETA',
            'date' => now(),
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
            'date' => now(),
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
            'date' => now(),
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

        $product = Product::create([
            'sku' => 'SKU-001',
            'name' => 'Product 1',
            'unit_id' => $this->unit->id,
            'track_stock' => false,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'IV-STORNO',
            'date' => now(),
            'total_amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'completed',
            'cogs_amount' => 5000,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'unit_id' => $this->unit->id,
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
