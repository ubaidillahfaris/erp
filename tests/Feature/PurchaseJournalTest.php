<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Journalable;
use App\Models\Purchase;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PurchaseJournalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup COA required for purchase integration
        Account::create(['code' => '1101', 'name' => 'Kas', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1301', 'name' => 'Persediaan', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '2101', 'name' => 'Hutang', 'type' => 'liability', 'balance_type' => 'credit']);
        
        $this->vendor = Vendor::factory()->create(['nama' => 'Vendor Test']);
    }

    /**
     * Test successful journaling when a purchase is finalized.
     */
    public function test_purchase_records_journal_on_finalized_status(): void
    {
        $purchase = Purchase::factory()->create([
            'status' => 'draft',
            'vendor_id' => $this->vendor->id,
            'total_biaya' => 1000.50,
            'tanggal' => '2024-04-19',
            'payment_method' => 'credit',
        ]);

        // Status change triggers the observer
        $purchase->update(['status' => 'finalized']);

        // Verify Journal Entry creation
        $this->assertDatabaseHas('journal_entries', [
            'journalable_type' => Purchase::class,
            'journalable_id' => $purchase->id,
            'ref_number' => 'PUR-20240419-' . $purchase->id,
        ]);

        $journal = JournalEntry::where('journalable_id', $purchase->id)->firstOrFail();

        // Verify balanced items (Debit Material vs Credit Payable)
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $journal->id,
            'account_id' => Account::where('code', '1301')->first()->id,
            'debit' => 100050,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $journal->id,
            'account_id' => Account::where('code', '2101')->first()->id,
            'debit' => 0,
            'credit' => 100050,
        ]);
    }

    /**
     * Test exception handling and logging for invalid purchase amounts.
     */
    public function test_purchase_logs_error_on_invalid_amount(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn($message) => str_contains($message, 'Double-Entry Journaling failed'));

        $purchase = Purchase::factory()->create([
            'status' => 'draft',
            'total_biaya' => 0, // Invalid amount
        ]);

        $purchase->update(['status' => 'finalized']);

        // Transaction continues (Status updated)
        $this->assertEquals('finalized', $purchase->fresh()->status);

        // No journal record created
        $this->assertDatabaseCount('journal_entries', 0);
    }

    /**
     * Test that a journal failure does not cause a rollback of the purchase transaction.
     */
    public function test_purchase_does_not_rollback_on_journal_failure(): void
    {
        // Simulate missing accounts failure
        Account::whereIn('code', ['1301', '2101'])->delete();
        
        Log::shouldReceive('error')->atLeast()->once();

        $purchase = Purchase::factory()->create([
            'status' => 'draft',
            'total_biaya' => 500,
        ]);

        // This update should succeed despite journaling failure
        $purchase->update(['status' => 'finalized']);

        // Assert status persistence (Non-blocking journaling)
        $this->assertEquals('finalized', $purchase->fresh()->status);
    }
}
