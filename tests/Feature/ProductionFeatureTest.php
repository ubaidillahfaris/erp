<?php

namespace Tests\Feature;

use App\Actions\RecalculateHpp;
use App\Models\Account;
use App\Models\Bom;
use App\Models\Production;
use App\Models\Produk;
use App\Models\Satuan;
use App\Models\SatuanConversion;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ProductionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Satuan $kg;

    private Satuan $gr;

    private Satuan $liter;

    private Satuan $ml;

    private Bom $bom;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed required COA for production
        Account::create(['code' => '1301', 'name' => 'Bahan Baku', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1302', 'name' => 'Barang Jadi', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '5102', 'name' => 'Overhead', 'type' => 'expense', 'balance_type' => 'credit']);

        $this->user = User::factory()->superadmin()->create();
        $this->actingAs($this->user);

        // Setup base units
        $this->kg = Satuan::create(['nama' => 'Kilogram', 'simbol' => 'kg', 'is_base' => true]);
        $this->gr = Satuan::create(['nama' => 'Gram', 'simbol' => 'gr', 'is_base' => false]);
        $this->liter = Satuan::create(['nama' => 'Liter', 'simbol' => 'L', 'is_base' => true]);
        $this->ml = Satuan::create(['nama' => 'Mililiter', 'simbol' => 'ml', 'is_base' => false]);

        $fg = Produk::create(['nama' => 'FG', 'sku' => 'FG-BASE', 'satuan_id' => $this->kg->id]);
        $this->bom = Bom::create(['produk_id' => $fg->id, 'sku' => 'BOM-BASE', 'is_active' => true]);

        // Setup conversions
        SatuanConversion::create(['satuan_id' => $this->kg->id, 'to_satuan_id' => $this->gr->id, 'rasio' => 1000]);
        SatuanConversion::create(['satuan_id' => $this->liter->id, 'to_satuan_id' => $this->ml->id, 'rasio' => 1000]);
        // 1 ml air = 1 gr
        SatuanConversion::create(['satuan_id' => $this->ml->id, 'to_satuan_id' => $this->gr->id, 'rasio' => 1]);
    }

    public function test_can_complete_production_with_valid_yield()
    {
        // 1. Setup ingredients with prices
        $kopiBubuk = Produk::create(['nama' => 'Kopi Bubuk', 'type' => 'raw_material', 'satuan_id' => $this->gr->id, 'sku' => '123']);
        $kopiBubuk->currentPrice()->create(['purchase_price' => 150, 'retail_price' => 0, 'satuan_id' => $this->gr->id]); // 150/gr
        Stock::create(['produk_id' => $kopiBubuk->id, 'balance' => 1000, 'last_satuan_id' => $this->gr->id]);

        $air = Produk::create(['nama' => 'Air Mineral', 'type' => 'raw_material', 'satuan_id' => $this->ml->id, 'sku' => '124']);
        $air->currentPrice()->create(['purchase_price' => 2, 'retail_price' => 0, 'satuan_id' => $this->ml->id]); // 2/ml
        Stock::create(['produk_id' => $air->id, 'balance' => 2000, 'last_satuan_id' => $this->ml->id]);

        // 2. Setup Finished Good
        $baseKopi = Produk::create([
            'nama' => 'Base Kopi',
            'type' => 'intermediate_good',
            'satuan_id' => $this->liter->id,
            'sku' => '125',
            'overhead_rate_per_unit' => 100,
        ]);

        // 3. Setup BOM (requires 100gr kopi, 500ml air, expecting 400ml yield representing 0.4L)
        // Cost: (100 * 150) = 15,000 + (500 * 2) = 1,000 => 16,000 total
        // Expected Yield = 0.4 Liter. Theoretical HPP per Liter = 16,000 / 0.4 = 40,000.
        $bom = Bom::create(['produk_id' => $baseKopi->id, 'expected_yield' => 0.4, 'nama' => 'BOM Base Kopi']);
        $bom->items()->create(['produk_id' => $kopiBubuk->id, 'satuan_id' => $this->gr->id, 'jumlah' => 100]);
        $bom->items()->create(['produk_id' => $air->id, 'satuan_id' => $this->ml->id, 'jumlah' => 500]);

        // Ensure price is calculated
        app(RecalculateHpp::class)->handle($baseKopi);

        // Start production
        $response = $this->actingAs($this->user)->post(route('production.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'bom_id' => $bom->id,
            'produk_id' => $baseKopi->id,
            'target_yield' => 0.4,
            'items' => [
                ['produk_id' => $kopiBubuk->id, 'satuan_id' => $this->gr->id, 'planned_qty' => 100],
                ['produk_id' => $air->id, 'satuan_id' => $this->ml->id, 'planned_qty' => 500],
            ],
        ]);

        $production = Production::first();

        // Complete production using 415ml total actual yield = 0.415 L
        // And we accidentally used 110gr kopi instead of 100gr
        $completeResponse = $this->actingAs($this->user)->put(route('production.update', $production->id), [
            'actual_yield' => 0.415,
            'items' => [
                ['id' => $production->items[0]->id, 'produk_id' => $kopiBubuk->id, 'satuan_id' => $this->gr->id, 'actual_qty' => 110],
                ['id' => $production->items[1]->id, 'produk_id' => $air->id, 'satuan_id' => $this->ml->id, 'actual_qty' => 500],
            ],
        ]);

        $completeResponse->assertRedirect(route('production.index'));

        // Refresh instances
        $production->refresh();
        $this->assertEquals('completed', $production->status);

        // Assert Stock Deducted
        $kopiStock = Stock::where('produk_id', $kopiBubuk->id)->first();
        $this->assertEquals(1000 - 110, (float) $kopiStock->balance); // Actually deducted 110

        $baseKopiStock = Stock::where('produk_id', $baseKopi->id)->first();
        $this->assertEquals(0.415, (float) $baseKopiStock->balance);

        // Assert Cost is correct
        // 110gr * 150 = 16,500
        // 500ml * 2 = 1,000
        // Total Cost = 17,500
        $this->assertEquals(17500, $production->total_cost);
    }

    public function test_index_displays_production_list_with_estimated_costs()
    {
        $fgProduct = Produk::create([
            'sku' => 'FG-INDEX',
            'nama' => 'Finished Good Index',
            'type' => 'finished_good',
            'satuan_id' => $this->kg->id,
        ]);

        $bom = Bom::create(['produk_id' => $fgProduct->id, 'expected_yield' => 10, 'nama' => 'BOM 1']);

        $production = Production::create([
            'sku' => 'PRD-INDEX',
            'tanggal' => now(),
            'produk_id' => $fgProduct->id,
            'bom_id' => $bom->id,
            'target_yield' => 10,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user)->get(route('production.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('production/Index')
                ->has('productions.data', 1)
                ->where('productions.data.0.sku', 'PRD-INDEX')
            );
    }

    public function test_show_displays_production_details()
    {
        $fgProduct = Produk::create([
            'sku' => 'FG-SHOW',
            'nama' => 'Finished Good Show',
            'type' => 'finished_good',
            'satuan_id' => $this->kg->id,
        ]);

        $bom = Bom::create(['produk_id' => $fgProduct->id, 'expected_yield' => 10, 'nama' => 'BOM 1']);

        $production = Production::create([
            'sku' => 'PRD-SHOW',
            'tanggal' => now(),
            'produk_id' => $fgProduct->id,
            'bom_id' => $bom->id,
            'target_yield' => 10,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user)->get(route('production.show', $production))
            ->assertInertia(fn (Assert $page) => $page
                ->component('production/Show')
                ->where('production.sku', 'PRD-SHOW')
            );
    }

    public function test_bulk_destroy_skips_completed_productions()
    {
        $fgProduct = Produk::create(['sku' => 'FG-BULK', 'nama' => 'FG Bulk', 'satuan_id' => $this->kg->id]);
        $bom = Bom::create(['produk_id' => $fgProduct->id, 'expected_yield' => 10, 'nama' => 'BOM 1']);

        $inProgress = Production::create([
            'sku' => 'PRD-IP',
            'tanggal' => now(),
            'produk_id' => $fgProduct->id,
            'bom_id' => $bom->id,
            'target_yield' => 10,
            'status' => 'in_progress',
        ]);

        $completed = Production::create([
            'sku' => 'PRD-COMP',
            'tanggal' => now(),
            'produk_id' => $fgProduct->id,
            'bom_id' => $bom->id,
            'target_yield' => 10,
            'status' => 'completed',
        ]);

        $this->actingAs($this->user)->delete(route('production.bulk-destroy'), [
            'ids' => [$inProgress->id, $completed->id],
        ]);

        $this->assertDatabaseMissing('productions', ['id' => $inProgress->id]);
        $this->assertDatabaseHas('productions', ['id' => $completed->id]);
    }

    public function test_create_with_reproduce_from_populates_data()
    {
        $fgProduct = Produk::create(['sku' => 'FG-REPRO', 'nama' => 'FG Repro', 'satuan_id' => $this->kg->id]);
        $bom = Bom::create(['produk_id' => $fgProduct->id, 'expected_yield' => 10, 'nama' => 'BOM 1']);

        $source = Production::create([
            'sku' => 'PRD-SOURCE',
            'tanggal' => now(),
            'produk_id' => $fgProduct->id,
            'bom_id' => $bom->id,
            'target_yield' => 10,
            'status' => 'completed',
        ]);

        $this->actingAs($this->user)->get(route('production.create', ['reproduce_from' => $source->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reproduceFrom')
                ->where('reproduceFrom.sku', 'PRD-SOURCE')
            );
    }
    public function test_production_edit_view()
    {
        $production = Production::factory()->create(['status' => 'in_progress', 'bom_id' => $this->bom->id]);
        
        $this->get(route('production.edit', $production))
            ->assertInertia(fn (Assert $page) => $page
                ->component('production/Edit')
                ->has('production')
            );
    }

    public function test_production_destroy_fails_if_completed()
    {
        $production = Production::factory()->create(['status' => 'completed', 'bom_id' => $this->bom->id]);
        
        $response = $this->delete(route('production.destroy', $production));
        
        $response->assertForbidden();
    }

    public function test_production_destroy_success_if_in_progress()
    {
        $production = Production::factory()->create(['status' => 'in_progress', 'bom_id' => $this->bom->id]);
        
        $this->delete(route('production.destroy', $production))
            ->assertRedirect()
            ->assertSessionHas('success');
            
        $this->assertModelMissing($production);
    }

    public function test_production_update_fails_if_already_completed()
    {
        $production = Production::factory()->create(['status' => 'completed', 'bom_id' => $this->bom->id]);
        $item = $production->items()->create([
            'produk_id' => $this->bom->produk_id,
            'satuan_id' => $this->kg->id,
            'planned_qty' => 5,
            'actual_qty' => 5,
            'harga_satuan' => 100,
        ]);
        
        // Pass dummy data to bypass validation and hit the state guard
        $response = $this->put(route('production.update', $production), [
            'actual_yield' => 10,
            'items' => [[
                'id' => $item->id, 
                'produk_id' => $item->produk_id, 
                'satuan_id' => $item->satuan_id, 
                'actual_qty' => 5
            ]]
        ]);
        
        $response->assertForbidden();
    }
    public function test_production_update_handles_missing_overhead_exception()
    {
        $fg = Produk::create(['nama' => 'No Overhead', 'sku' => 'NO-OH', 'satuan_id' => $this->kg->id]);
        $bom = Bom::create(['produk_id' => $fg->id, 'sku' => 'BOM-NO-OH', 'is_active' => true]);
        $production = Production::factory()->create(['status' => 'in_progress', 'bom_id' => $bom->id, 'produk_id' => $fg->id]);
        $item = $production->items()->create([
            'produk_id' => $fg->id, // Use fg itself or another product
            'satuan_id' => $this->kg->id,
            'planned_qty' => 5,
            'actual_qty' => 5,
            'harga_satuan' => 100,
        ]);
        Stock::create(['produk_id' => $fg->id, 'balance' => 10, 'last_satuan_id' => $this->kg->id]);
        
        // This should trigger MissingOverheadRateException in CompleteProduction action
        $response = $this->put(route('production.update', $production), [
            'actual_yield' => 10,
            'items' => [[
                'id' => $item->id, 
                'produk_id' => $item->produk_id, 
                'satuan_id' => $item->satuan_id, 
                'actual_qty' => 5
            ]]
        ]);
        
        $response->assertRedirect()->assertSessionHas('error');
    }
    public function test_production_deletion_removes_stock_movements()
    {
        $production = Production::factory()->create(['status' => 'in_progress', 'bom_id' => $this->bom->id]);
        
        // Mock a movement
        StockMovement::create([
            'produk_id' => $this->bom->produk_id,
            'satuan_id' => $this->kg->id,
            'type' => 'in',
            'jumlah' => 1,
            'reference_type' => 'production_yield',
            'reference_id' => $production->id,
            'keterangan' => 'Test Yield',
        ]);
        
        $this->assertDatabaseHas('stock_movements', ['reference_id' => $production->id]);
        
        $production->delete();
        
        $this->assertDatabaseMissing('stock_movements', ['reference_id' => $production->id]);
    }
}




