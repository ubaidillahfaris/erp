<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\FinancialSummary;
use App\Models\Restock;
use App\Models\Pengeluaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_restock_creation_triggers_journal_and_summary(): void
    {
        $date = now()->startOfDay();
        $restock = Restock::create([
            'tanggal' => $date,
            'total_biaya' => 100000,
            'status_pembayaran' => 'lunas',
            'keterangan' => 'Test Restock',
        ]);

        // Verify Journal entry
        $this->assertDatabaseHas('journals', [
            'reference_type' => Restock::class,
            'reference_id' => $restock->id,
            'type' => 'kredit',
            'amount' => 100000,
        ]);

        // Verify Summary
        $summary = FinancialSummary::where('date', $date->format('Y-m-d'))->first();
        if (!$summary) {
            dump(FinancialSummary::all()->toArray());
        }
        $this->assertNotNull($summary, "FinancialSummary not found for date: " . $date->format('Y-m-d'));
        $this->assertEquals(100000, (float) $summary->total_kredit);
        $this->assertEquals(-100000, (float) $summary->final_balance);
    }

    public function test_pengeluaran_creation_triggers_journal_and_summary(): void
    {
        $date = now()->startOfDay();
        $pengeluaran = Pengeluaran::create([
            'tanggal' => $date,
            'jenis_pengeluaran' => 'Operasional',
            'nama_pengeluaran' => 'Listrik',
            'nominal' => 50000,
        ]);

        $this->assertDatabaseHas('journals', [
            'reference_type' => Pengeluaran::class,
            'reference_id' => $pengeluaran->id,
            'amount' => 50000,
        ]);

        $summary = FinancialSummary::where('date', $date->format('Y-m-d'))->first();
        if (!$summary) {
            dump(FinancialSummary::all()->toArray());
        }
        $this->assertNotNull($summary, "FinancialSummary not found for date: " . $date->format('Y-m-d'));
        $this->assertEquals(50000, (float) $summary->total_kredit);
    }

    public function test_restock_deletion_reverts_journal_and_summary(): void
    {
        $date = now()->startOfDay();
        $restock = Restock::create([
            'tanggal' => $date,
            'total_biaya' => 100000,
            'status_pembayaran' => 'lunas',
        ]);

        $summary = FinancialSummary::where('date', $date->format('Y-m-d'))->first();
        $this->assertNotNull($summary, "FinancialSummary not found before deletion");
        $this->assertEquals(100000, (float) $summary->total_kredit);

        $restock->delete();

        $this->assertDatabaseMissing('journals', [
            'reference_type' => Restock::class,
            'reference_id' => $restock->id,
        ]);

        $summary = FinancialSummary::where('date', $date->format('Y-m-d'))->first();
        $this->assertEquals(0, (float) $summary->total_kredit);
        $this->assertEquals(0, (float) $summary->final_balance);
    }
}
