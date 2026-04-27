<?php

namespace App\Observers;

use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payable;
use App\Models\Restock;
use App\Models\StockMovement;
use App\Services\JournalService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;

class RestockObserver
{
    public function __construct(protected JournalService $journalService) {}

    /**
     * Handle the Restock "created" event.
     */
    public function created(Restock $restock): void
    {
        $this->recordRestockJournal($restock);

        $date = $restock->date instanceof CarbonInterface ? $restock->date->format('Y-m-d') : $restock->date;

        // Journal creation (DISABLED - Transition to Double-Entry)
        /*
        \App\Models\Journal::create([
            'date' => $date,
            'type' => 'kredit',
            'amount' => $restock->total_biaya,
            'category' => 'persediaan',
            'payment_method' => $restock->status_pembayaran === 'lunas' ? 'tunai' : 'hutang',
            'reference_type' => Restock::class,
            'reference_id' => $restock->id,
            'description' => 'Pembelian stok: '.($restock->notes ?? 'Tanpa keterangan'),
        ]);
        */

        // Auto-create Payable
        if (in_array($restock->status_pembayaran, ['hutang', 'bayar_berkala'])) {
            $principal = $restock->total_biaya;
            if ($restock->status_pembayaran === 'bayar_berkala') {
                $principal = $restock->total_biaya - $restock->total_bayar;
            }

            Payable::create([
                'type' => 'payable',
                'reference_type' => 'restock',
                'reference_id' => $restock->id,
                'party_type' => 'vendor',
                'party_id' => $restock->vendor_id,
                'principal_amount' => $principal,
                'total_amount' => $principal,
                'total_interest' => 0,
                'status' => 'open',
                'created_by' => Auth::id(),
            ]);
        }
    }

    public function updated(Restock $restock): void
    {
        $date = $restock->date instanceof CarbonInterface ? $restock->date->format('Y-m-d') : $restock->date;

        // Journal update DISABLED - Transition to Double-Entry
        /*
        $journal = Journal::where('reference_type', Restock::class)
            ->where('reference_id', $restock->id)
            ->first();

        if ($journal) {
            $journal->update([
                'date' => $date,
                'amount' => $restock->total_biaya,
                'payment_method' => $restock->status_pembayaran === 'lunas' ? 'tunai' : 'hutang',
                'description' => 'Pembelian stok: '.($restock->notes ?? 'Tanpa keterangan'),
            ]);
        }
        */

        // Update Payable status if changed to lunas
        if ($restock->isDirty('status_pembayaran') && $restock->status_pembayaran === 'lunas') {
            Payable::where('reference_type', 'restock')
                ->where('reference_id', $restock->id)
                ->update(['status' => 'paid']);

            // Record settlement journal if it was previously hhutang
            // For now, simplicity: just record the journal item for payment
            // Actually, we'll just re-sync or handle it in recordRestockJournal
            $this->recordRestockJournal($restock);
        }
    }

    protected function recordRestockJournal(Restock $restock): void
    {
        $materialAcc = Account::findByCode('1301');
        $paymentAcc = $restock->status_pembayaran === 'lunas'
            ? Account::findByCode('1101')
            : Account::findByCode('2101');

        $amountCents = (int) round((float) $restock->total_biaya * 100);

        $this->journalService->record(new JournalEntryData(
            items: [
                new JournalItemData($materialAcc->id, $amountCents, 'debit'),
                new JournalItemData($paymentAcc->id, $amountCents, 'credit'),
            ],
            date: $restock->date,
            description: 'Restock [Legacy]: '.($restock->notes ?? 'Tanpa keterangan'),
            journalable: $restock
        ));
    }

    /**
     * Handle the Restock "deleted" event.
     */
    public function deleted(Restock $restock): void
    {
        // Delete stock movements via instance to trigger StockMovementObserver
        StockMovement::where('reference_type', 'restock')
            ->where('reference_id', $restock->id)
            ->get()
            ->each->delete();

        // Delete journal entries (Transition to Double-Entry)
        JournalEntry::where('journalable_type', Restock::class)
            ->where('journalable_id', $restock->id)
            ->delete();

        // Delete associated payables
        Payable::where('reference_type', 'restock')
            ->where('reference_id', $restock->id)
            ->delete();
    }
}
