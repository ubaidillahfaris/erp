<?php

namespace Tests\Feature\Sprint3;

use App\Models\Account;
use App\Models\PeriodLock;
use App\Models\Production;
use App\Models\Sale;
use App\Models\Satuan;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HardeningIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Satuan $satuan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->superadmin()->create();
        $this->satuan = Satuan::factory()->create(['nama' => 'pcs']);

        // Setup essential accounts
        Account::create(['code' => '1101', 'name' => 'Cash', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1302', 'name' => 'Inventory', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '4101', 'name' => 'Sales', 'type' => 'income', 'balance_type' => 'credit']);
        Account::create(['code' => '4102', 'name' => 'Other Income', 'type' => 'income', 'balance_type' => 'credit']);
        Account::create(['code' => '5101', 'name' => 'COGS', 'type' => 'expense', 'balance_type' => 'debit']);
        Account::create(['code' => '6201', 'name' => 'Shrinkage', 'type' => 'expense', 'balance_type' => 'debit']);

        // Create and Give permissions
        Permission::create(['name' => 'make sales']);
        Permission::create(['name' => 'void sales']);
        Permission::create(['name' => 'manage stock']);
        Permission::create(['name' => 'manage products']);

        $this->admin->givePermissionTo('make sales', 'void sales', 'manage stock', 'manage products');
    }

    /** @test */
    public function test_sales_controller_listing_and_show()
    {
        Sale::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)->get(route('sales.index'));
        $response->assertStatus(200);

        $sale = Sale::first();
        $response = $this->actingAs($this->admin)->get(route('sales.show', $sale));
        $response->assertStatus(200);
    }

    /** @test */
    public function test_storno_sale_is_blocked_by_period_lock()
    {
        $sale = Sale::factory()->create(['tanggal' => now()]);

        PeriodLock::create([
            'period_month' => now()->month,
            'period_year' => now()->year,
            'is_locked' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('sales.void', $sale), [
            'reason' => 'Wrong item sold',
        ]);

        $response->assertStatus(403);
        $this->assertNull($sale->fresh()->storno_at);
    }

    /** @test */
    public function test_stock_opname_listing_and_storno()
    {
        $opname = StockOpname::create([
            'tanggal' => now(),
            'status' => 'completed',
            'keterangan' => 'Monthly Check',
        ]);

        $response = $this->actingAs($this->admin)->get(route('stock-opname.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->post(route('stock-opname.storno', $opname), [
            'reason' => 'Mistake in count',
        ]);

        $response->assertRedirect();
        $this->assertNotNull($opname->fresh()->storno_at);
    }

    /** @test */
    public function test_production_is_protected_by_state_guard()
    {
        $production = Production::factory()->create([
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('production.destroy', $production));

        $response->assertStatus(403);
    }

    /** @test */
    public function test_period_lock_blocks_various_financial_routes()
    {
        PeriodLock::create([
            'period_month' => now()->month,
            'period_year' => now()->year,
            'is_locked' => true,
        ]);

        // 1. POS Store
        $this->actingAs($this->admin)
            ->post(route('pos.store'), ['tanggal' => now()->toDateString()])
            ->assertStatus(403);

        // 2. Production Store
        $this->actingAs($this->admin)
            ->post(route('production.store'), ['tanggal' => now()->toDateString()])
            ->assertStatus(403);

        // 3. Stock Adjustment
        $this->actingAs($this->admin)
            ->post(route('stock.adjustment'), ['tanggal' => now()->toDateString()])
            ->assertStatus(403);
    }
}
