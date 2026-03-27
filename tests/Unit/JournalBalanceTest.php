<?php

namespace Tests\Unit;

use App\Models\Journal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_balance_correctly_on_creation(): void
    {
        // Entry 1
        $j1 = Journal::factory()->create(['tanggal' => '2023-01-01', 'type' => 'debit', 'amount' => 1000]);
        $this->assertEquals(1000, $j1->fresh()->balance);

        // Entry 2
        $j2 = Journal::factory()->create(['tanggal' => '2023-01-02', 'type' => 'kredit', 'amount' => 300]);
        $this->assertEquals(700, $j2->fresh()->balance);

        // Entry 3 (Newest)
        $j3 = Journal::factory()->create(['tanggal' => '2023-01-03', 'type' => 'debit', 'amount' => 500]);
        $this->assertEquals(1200, $j3->fresh()->balance);
    }

    public function test_it_recalculates_balance_when_middle_record_inserted(): void
    {
        // Entry 1
        $j1 = Journal::factory()->create(['tanggal' => '2023-01-01', 'type' => 'debit', 'amount' => 1000]);
        // Entry 3
        $j3 = Journal::factory()->create(['tanggal' => '2023-01-03', 'type' => 'debit', 'amount' => 500]);
        
        $this->assertEquals(1500, $j3->fresh()->balance);

        // Insert Entry 2 (Middle)
        $j2 = Journal::factory()->create(['tanggal' => '2023-01-02', 'type' => 'kredit', 'amount' => 300]);
        
        $this->assertEquals(700, $j2->fresh()->balance);
        $this->assertEquals(1200, $j3->fresh()->balance);
    }

    public function test_backfill_logic(): void
    {
        // Disable observer for initial setup to mock "old data" without balance
        Journal::flushEventListeners();

        Journal::create(['tanggal' => '2023-01-01', 'type' => 'debit', 'amount' => 1000, 'category' => 'test', 'payment_method' => 'cash', 'balance' => 0]);
        Journal::create(['tanggal' => '2023-01-02', 'type' => 'kredit', 'amount' => 200, 'category' => 'test', 'payment_method' => 'cash', 'balance' => 0]);
        Journal::create(['tanggal' => '2023-01-03', 'type' => 'debit', 'amount' => 300, 'category' => 'test', 'payment_method' => 'cash', 'balance' => 0]);

        $this->backfillBalances();

        $journals = Journal::orderBy('tanggal', 'asc')->get();
        $this->assertEquals(1000, $journals[0]->balance);
        $this->assertEquals(800, $journals[1]->balance);
        $this->assertEquals(1100, $journals[2]->balance);
    }

    private function backfillBalances(): void
    {
        $currentBalance = 0;
        $journals = Journal::orderBy('tanggal', 'asc')->orderBy('id', 'asc')->get();

        foreach ($journals as $journal) {
            $impact = ($journal->type === 'debit' ? (float) $journal->amount : -(float) $journal->amount);
            $currentBalance += $impact;
            
            $journal->updateQuietly(['balance' => $currentBalance]);
        }
    }
}
