<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Pengeluaran;
use App\Models\Restock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic accounts for integration tests
        Account::create(['code' => '1101', 'name' => 'Kas', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1301', 'name' => 'Persediaan', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '2101', 'name' => 'Hutang', 'type' => 'liability', 'balance_type' => 'credit']);
        Account::create(['code' => '6201', 'name' => 'Beban Operasional', 'type' => 'expense', 'balance_type' => 'debit']);
    }

    public function test_restock_creation_triggers_journal(): void
    {
        $date = now()->startOfDay();
        $restock = Restock::create([
            'tanggal' => $date,
            'total_biaya' => 100000,
            'status_pembayaran' => 'lunas',
            'keterangan' => 'Test Restock',
        ]);

        // Verify Journal Entry (Double-Entry)
        $this->assertDatabaseHas('journal_entries', [
            'journalable_type' => Restock::class,
            'journalable_id' => $restock->id,
        ]);

        $entry = JournalEntry::where('journalable_id', $restock->id)->first();

        // Verify Balanced Items (Debit Inventory vs Credit Cash)
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $entry->id,
            'account_id' => Account::where('code', '1301')->first()->id,
            'debit' => 10000000, // Cents
        ]);

        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $entry->id,
            'account_id' => Account::where('code', '1101')->first()->id,
            'credit' => 10000000, // Cents
        ]);
    }

    public function test_pengeluaran_creation_triggers_journal(): void
    {
        $date = now()->startOfDay();
        $pengeluaran = Pengeluaran::create([
            'tanggal' => $date,
            'jenis_pengeluaran' => 'Operasional',
            'nama_pengeluaran' => 'Listrik',
            'nominal' => 50000,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'journalable_type' => Pengeluaran::class,
            'journalable_id' => $pengeluaran->id,
        ]);

        $entry = JournalEntry::where('journalable_id', $pengeluaran->id)->first();

        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $entry->id,
            'account_id' => Account::where('code', '6201')->first()->id,
            'debit' => 5000000, // Cents
        ]);
    }

    public function test_restock_deletion_reverts_journal(): void
    {
        $date = now()->startOfDay();
        $restock = Restock::create([
            'tanggal' => $date,
            'total_biaya' => 100000,
            'status_pembayaran' => 'lunas',
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'journalable_type' => Restock::class,
            'journalable_id' => $restock->id,
        ]);

        $restock->delete();

        $this->assertDatabaseMissing('journal_entries', [
            'journalable_type' => Restock::class,
            'journalable_id' => $restock->id,
        ]);
    }
}
