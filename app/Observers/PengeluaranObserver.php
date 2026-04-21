<?php

namespace App\Observers;

use App\Models\Pengeluaran;
use App\Models\Account;
use App\Services\JournalService;
use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use Illuminate\Support\Facades\Log;

class PengeluaranObserver
{
    public function __construct(protected JournalService $journalService)
    {
    }

    /**
     * Handle the Pengeluaran "created" event.
     */
    public function created(Pengeluaran $pengeluaran): void
    {
        $this->recordJournal($pengeluaran);
    }

    /**
     * Handle the Pengeluaran "updated" event.
     */
    public function updated(Pengeluaran $pengeluaran): void
    {
        // Delete old journal and create new one for simplicity in this module
        $this->deleted($pengeluaran);
        $this->recordJournal($pengeluaran);
    }

    /**
     * Handle the Pengeluaran "deleted" event.
     */
    public function deleted(Pengeluaran $pengeluaran): void
    {
        try {
            \App\Models\JournalEntry::where('journalable_type', Pengeluaran::class)
                ->where('journalable_id', $pengeluaran->id)
                ->delete();
        } catch (\Exception $e) {
            Log::error("Failed to delete journal for Pengeluaran #{$pengeluaran->id}: " . $e->getMessage());
        }
    }

    protected function recordJournal(Pengeluaran $pengeluaran): void
    {
        try {
            // 1. Determine accounts
            // Dr. Expense Account (from pengeluaran or default to 6201)
            // Cr. Kas & Bank (1101)
            $expenseAccount = $pengeluaran->account_id 
                ? Account::find($pengeluaran->account_id) 
                : Account::where('code', '6201')->first();

            $cashAccount = Account::where('code', '1101')->first();

            if (!$expenseAccount || !$cashAccount) {
                Log::warning("Skipping journal for Pengeluaran #{$pengeluaran->id}: Missing accounts (6201/1101).");
                return;
            }

            // 2. Prepare Data (Amounts in cents)
            $amountCents = (int) round($pengeluaran->nominal * 100);
            
            if ($amountCents <= 0) return;

            $items = [
                new JournalItemData(
                    account_id: $expenseAccount->id,
                    type: 'debit',
                    amount: $amountCents
                ),
                new JournalItemData(
                    account_id: $cashAccount->id,
                    type: 'credit',
                    amount: $amountCents
                ),
            ];

            $data = new JournalEntryData(
                items: $items,
                description: "Pengeluaran Operasional: {$pengeluaran->nama_pengeluaran} ({$pengeluaran->jenis_pengeluaran})",
                tanggal: $pengeluaran->tanggal,
                ref_number: "EXP-" . $pengeluaran->tanggal->format('Ymd') . "-" . str_pad((string)$pengeluaran->id, 4, '0', STR_PAD_LEFT),
                journalable: $pengeluaran
            );

            $this->journalService->record($data);
            
            // Legacy Journal (DISABLED - Transition to Double-Entry)
            /*
            \App\Models\Journal::create([
                'tanggal' => $pengeluaran->tanggal->format('Y-m-d'),
                'type' => 'kredit',
                'amount' => $pengeluaran->nominal,
                'category' => 'operasional',
                'description' => "Pengeluaran Operasional: {$pengeluaran->nama_pengeluaran}",
                'reference_type' => Pengeluaran::class,
                'reference_id' => $pengeluaran->id,
            ]);
            */

        } catch (\Exception $e) {
            Log::error("Failed to record journal for Pengeluaran #{$pengeluaran->id}: " . $e->getMessage());
        }
    }
}
