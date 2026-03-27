<?php

namespace App\Observers;

use App\Models\Restock;
use App\Models\StockMovement;
use App\Models\Journal;

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
            'description' => "Pembelian stok: " . ($restock->keterangan ?? 'Tanpa keterangan'),
        ]);
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
                'description' => "Pembelian stok: " . ($restock->keterangan ?? 'Tanpa keterangan'),
            ]);
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
    }
}
