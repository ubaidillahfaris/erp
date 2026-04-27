<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductPriceStat;
use App\Models\StockOpname;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockOpnameJournalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Product $product;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Role::create(['name' => 'superadmin']);
        $this->user->assignRole('superadmin');

        $this->unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);

        $this->product = Product::create([
            'name' => 'Roti Tawar',
            'unit_id' => $this->unit->id,
            'kategori' => 'Bakery',
            'harga_jual' => 12000,
            'stock_minimum' => 10,
        ]);

        // Create the required accounts
        Account::firstOrCreate(['code' => '1101'], ['name' => 'Kas', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::firstOrCreate(['code' => '1301'], ['name' => 'Persediaan', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::firstOrCreate(['code' => '4102'], ['name' => 'Pendapatan Lain-lain', 'type' => 'income', 'balance_type' => 'credit']);
        Account::firstOrCreate(['code' => '6201'], ['name' => 'Kerugian Selisih Stok', 'type' => 'expense', 'balance_type' => 'debit']);

        ProductPriceStat::create([
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'avg_price' => 10000, // 10k -> 1000000 cents
            'latest_price' => 10000,
        ]);

        $this->actingAs($this->user);
    }

    public function test_surplus_item_records_debit_persediaan()
    {
        $payload = [
            'date' => now()->format('Y-m-d'),
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'system_qty' => 10,
                    'physical_qty' => 12, // +2 surplus
                ],
            ],
        ];

        $response = $this->post(route('stock-opname.store'), $payload);
        $response->assertSessionHas('success');

        $opname = StockOpname::first();
        $this->assertNotNull($opname);

        $journal = JournalEntry::where('journalable_type', StockOpname::class)
            ->where('journalable_id', $opname->id)
            ->with('items.account')
            ->first();

        $this->assertNotNull($journal);
        $this->assertCount(2, $journal->items);

        $debitItem = $journal->items->where('debit', '>', 0)->first();
        $creditItem = $journal->items->where('credit', '>', 0)->first();

        $this->assertEquals('1301', $debitItem->account->code);
        $this->assertEquals('4102', $creditItem->account->code);

        // 2 qty * 10000 * 100 cents = 2000000 cents
        $this->assertEquals(2000000, $debitItem->debit);
        $this->assertEquals(2000000, $creditItem->credit);
    }

    public function test_shrinkage_item_records_debit_kerugian()
    {
        $payload = [
            'date' => now()->format('Y-m-d'),
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'system_qty' => 10,
                    'physical_qty' => 8, // -2 shrinkage
                ],
            ],
        ];

        $response = $this->post(route('stock-opname.store'), $payload);
        $response->assertSessionHas('success');

        $opname = StockOpname::first();
        $journal = JournalEntry::where('journalable_type', StockOpname::class)
            ->where('journalable_id', $opname->id)
            ->with('items.account')
            ->first();

        $this->assertNotNull($journal);

        $debitItem = $journal->items->where('debit', '>', 0)->first();
        $creditItem = $journal->items->where('credit', '>', 0)->first();

        $this->assertEquals('6201', $debitItem->account->code);
        $this->assertEquals('1301', $creditItem->account->code);

        // 2 qty * 10000 * 100 cents = 2000000 cents
        $this->assertEquals(2000000, $debitItem->debit);
        $this->assertEquals(2000000, $creditItem->credit);
    }

    public function test_zero_value_item_skips_journal()
    {
        // Change avg_price to 0 temporarily
        ProductPriceStat::where('product_id', $this->product->id)->update(['avg_price' => 0]);

        $payload = [
            'date' => now()->format('Y-m-d'),
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'system_qty' => 10,
                    'physical_qty' => 8, // -2 shrinkage, but 0 value
                ],
            ],
        ];

        $response = $this->post(route('stock-opname.store'), $payload);
        $response->assertSessionHas('success');

        $opname = StockOpname::first();
        $journalCount = JournalEntry::where('journalable_type', StockOpname::class)
            ->where('journalable_id', $opname->id)
            ->count();

        // No journal should be created
        $this->assertEquals(0, $journalCount);
    }

    public function test_journal_failure_does_not_rollback_finalization()
    {
        // Deliberately cause a failure by removing required account 1301
        Account::where('code', '1301')->delete();

        $payload = [
            'date' => now()->format('Y-m-d'),
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'system_qty' => 10,
                    'physical_qty' => 12, // +2 surplus
                ],
            ],
        ];

        // Should still succeed the finalization
        $response = $this->post(route('stock-opname.store'), $payload);
        $response->assertSessionHas('success');

        $opname = StockOpname::first();
        $this->assertEquals('completed', $opname->status);

        // Movement should be recorded
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => 'stock_opname',
            'reference_id' => $opname->id,
        ]);

        // Journal should NOT exist because it failed inside try/catch
        $journalCount = JournalEntry::where('journalable_type', StockOpname::class)
            ->where('journalable_id', $opname->id)
            ->count();
        $this->assertEquals(0, $journalCount);
    }

    public function test_unit_price_populated_from_avg_price_on_create()
    {
        $payload = [
            'date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'system_qty' => 10,
                    'physical_qty' => 12,
                ],
            ],
        ];

        $this->post(route('stock-opname.store'), $payload);

        $opname = StockOpname::first();
        $item = $opname->items()->first();

        // 10000 * 100 = 1000000
        $this->assertEquals(1000000, $item->unit_price);
    }
}
