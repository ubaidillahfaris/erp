<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerStatus;
use App\Models\CustomerType;
use App\Models\Price;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PosControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Unit $unit;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->superadmin()->create();
        $this->actingAs($this->user);

        $this->unit = Unit::create(['name' => 'PCS', 'symbol' => 'pcs']);
        $this->category = Category::create(['name' => 'Bakery', 'slug' => 'bakery']);

        // Required Accounts for Sale Observer
        Account::create(['code' => '1101', 'name' => 'Cash', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1102', 'name' => 'Receivable', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1302', 'name' => 'Finished Goods', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '4101', 'name' => 'Sales', 'type' => 'income', 'balance_type' => 'credit']);
        Account::create(['code' => '5101', 'name' => 'COGS', 'type' => 'expense', 'balance_type' => 'debit']);

        // Setup Customer dependencies
        CustomerStatus::create(['name' => 'Active']);
        CustomerType::create(['name' => 'Regular']);
        CustomerType::create(['name' => 'Wholesale']);
    }

    public function test_index_displays_pos_screen_with_data()
    {
        Product::create([
            'sku' => 'PROD-001',
            'name' => 'Product 1',
            'type' => 'finished_good',
            'unit_id' => $this->unit->id,
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $this->get(route('pos.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pos/Index')
                ->has('products')
                ->has('customers')
                ->has('categories')
            );
    }

    public function test_get_price_returns_correct_retail_price()
    {
        $product = Product::create([
            'sku' => 'PROD-002',
            'name' => 'Product 2',
            'type' => 'finished_good',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        Price::create([
            'product_id' => $product->id,
            'unit_id' => $this->unit->id,
            'retail_price' => 10000,
            'purchase_price' => 8000,
            'is_current' => true,
        ]);

        $response = $this->getJson(route('pos.price', [
            'product_id' => $product->id,
            'unit_id' => $this->unit->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('price', 10000)
            ->assertJsonPath('price_type', 'retail');
    }

    public function test_get_price_applies_wholesale_price_for_wholesale_customer()
    {
        $wholesaleType = CustomerType::where('name', 'Wholesale')->first();
        $status = CustomerStatus::where('name', 'Active')->first();

        $customer = Customer::create([
            'name' => 'Wholesale Customer',
            'customer_type_id' => $wholesaleType->id,
            'customer_status_id' => $status->id,
        ]);

        $product = Product::create([
            'sku' => 'PROD-003',
            'name' => 'Product 3',
            'type' => 'finished_good',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        Price::create([
            'product_id' => $product->id,
            'unit_id' => $this->unit->id,
            'retail_price' => 10000,
            'wholesale_price' => 9000,
            'purchase_price' => 8000,
            'is_current' => true,
        ]);

        $response = $this->getJson(route('pos.price', [
            'product_id' => $product->id,
            'unit_id' => $this->unit->id,
            'customer_id' => $customer->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('price', 9000)
            ->assertJsonPath('price_type', 'wholesale');
    }

    public function test_store_creates_sale_successfully()
    {
        $product = Product::create([
            'sku' => 'PROD-004',
            'name' => 'Product 4',
            'type' => 'finished_good',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'last_unit_id' => $this->unit->id,
            'balance' => 100,
        ]);

        $payload = [
            'date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $this->unit->id,
                    'qty' => 2,
                    'price' => 5000,
                    'cost' => 3000,
                ],
            ],
            'received_amount' => 10000,
            'change_amount' => 0,
        ];

        $this->post(route('pos.store'), $payload)
            ->assertRedirect(route('pos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('sales', [
            'total_amount' => 10000,
            'status' => 'completed',
        ]);
    }

    public function test_store_fails_if_stock_insufficient()
    {
        $product = Product::create([
            'sku' => 'PROD-005',
            'name' => 'Product 5',
            'type' => 'finished_good',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'last_unit_id' => $this->unit->id,
            'balance' => 1, // Only 1 in stock
        ]);

        $payload = [
            'date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $this->unit->id,
                    'qty' => 5, // Requesting 5
                    'price' => 5000,
                    'cost' => 3000,
                ],
            ],
        ];

        $this->post(route('pos.store'), $payload)
            ->assertSessionHasErrors(['checkout']);

        $this->assertDatabaseMissing('sales', ['status' => 'completed']);
    }
}
