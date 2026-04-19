<?php

namespace App\Observers;

use App\Models\Journal;
use App\Models\Payable;
use App\Models\Restock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

class RestockObserver
{
    /**
     * Handle the Restock "created" event.
     */
    public function created(Restock $restock): void
    {
        $tanggal = $restock->tanggal instanceof \Carbon\CarbonInterface ? $restock->tanggal->format('Y-m-d') : $restock->tanggal;

        Journal::create([
            'tanggal' => $tanggal,
            'type' => 'kredit',
            'amount' => $restock->total_biaya,
            'category' => 'persediaan',
            'payment_method' => $restock->status_pembayaran === 'lunas' ? 'tunai' : 'hutang',
            'reference_type' => Restock::class,
            'reference_id' => $restock->id,
            'description' => 'Pembelian stok: '.($restock->keterangan ?? 'Tanpa keterangan'),
        ]);

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
        $tanggal = $restock->tanggal instanceof \Carbon\CarbonInterface ? $restock->tanggal->format('Y-m-d') : $restock->tanggal;

        $journal = Journal::where('reference_type', Restock::class)
            ->where('reference_id', $restock->id)
            ->first();

        if ($journal) {
            $journal->update([
                'tanggal' => $tanggal,
                'amount' => $restock->total_biaya,
                'payment_method' => $restock->status_pembayaran === 'lunas' ? 'tunai' : 'hutang',
                'description' => 'Pembelian stok: '.($restock->keterangan ?? 'Tanpa keterangan'),
            ]);
        }

        // Update Payable status if changed to lunas
        if ($restock->isDirty('status_pembayaran') && $restock->status_pembayaran === 'lunas') {
            Payable::where('reference_type', 'restock')
                ->where('reference_id', $restock->id)
                ->update(['status' => 'paid']);
        }
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

        // Delete journal entries via instance to trigger JournalObserver
        Journal::where('reference_type', Restock::class)
            ->where('reference_id', $restock->id)
            ->get()
            ->each->delete();

        // Delete associated payables
        Payable::where('reference_type', 'restock')
            ->where('reference_id', $restock->id)
            ->delete();
    }
}
