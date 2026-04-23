<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
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

    protected function setUp(): void
    {
        parent::setUp();

        Account::create(['code' => '1101', 'name' => 'Cash', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1102', 'name' => 'Receivable', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1302', 'name' => 'Finished Goods', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '4101', 'name' => 'Sales', 'type' => 'income', 'balance_type' => 'credit']);
        Account::create(['code' => '5101', 'name' => 'COGS', 'type' => 'expense', 'balance_type' => 'debit']);
    }

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

        // 3. Assert Double-Entry Journaling
        // Verify Revenue Entry
        $this->assertDatabaseHas('journal_entries', [
            'journalable_type' => Sale::class,
            'journalable_id' => $sale->id,
            'description' => "Revenue Penjualan INV-{$sale->invoice_number}",
        ]);

        // Verify COGS Entry
        $this->assertDatabaseHas('journal_entries', [
            'journalable_type' => Sale::class,
            'journalable_id' => $sale->id,
            'description' => "COGS Penjualan INV-{$sale->invoice_number}",
        ]);

        // Verify some items
        $revenueEntry = JournalEntry::where('journalable_id', $sale->id)
            ->where('description', 'LIKE', '%Revenue%')
            ->first();

        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $revenueEntry->id,
            'account_id' => Account::where('code', '4101')->first()->id,
            'credit' => 1250000, // 12500 in cents
        ]);

        $cogsEntry = JournalEntry::where('journalable_id', $sale->id)
            ->where('description', 'LIKE', '%COGS%')
            ->first();

        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $cogsEntry->id,
            'account_id' => Account::where('code', '5101')->first()->id,
            'debit' => 500000, // 5000 in cents
        ]);
    }
}
