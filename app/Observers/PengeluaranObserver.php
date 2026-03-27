<?php

namespace App\Observers;

use App\Models\Pengeluaran;
use App\Models\Journal;

class PengeluaranObserver
{
    /**
     * Handle the Pengeluaran "created" event.
     */
    public function created(Pengeluaran $pengeluaran): void
    {
        $tanggal = $pengeluaran->tanggal instanceof \Carbon\CarbonInterface ? $pengeluaran->tanggal->format('Y-m-d') : $pengeluaran->tanggal;

        Journal::create([
            'tanggal' => $tanggal,
            'type' => 'kredit',
            'amount' => $pengeluaran->nominal,
            'category' => 'beban',
            'payment_method' => 'tunai',
            'reference_type' => Pengeluaran::class,
            'reference_id' => $pengeluaran->id,
            'description' => "{$pengeluaran->jenis_pengeluaran}: {$pengeluaran->nama_pengeluaran}" . ($pengeluaran->keterangan ? " ({$pengeluaran->keterangan})" : ""),
        ]);
    }

    public function updated(Pengeluaran $pengeluaran): void
    {
        $tanggal = $pengeluaran->tanggal instanceof \Carbon\CarbonInterface ? $pengeluaran->tanggal->format('Y-m-d') : $pengeluaran->tanggal;

        $journal = Journal::where('reference_type', Pengeluaran::class)
            ->where('reference_id', $pengeluaran->id)
            ->first();

        if ($journal) {
            $journal->update([
                'tanggal' => $tanggal,
                'amount' => $pengeluaran->nominal,
                'description' => "{$pengeluaran->jenis_pengeluaran}: {$pengeluaran->nama_pengeluaran}" . ($pengeluaran->keterangan ? " ({$pengeluaran->keterangan})" : ""),
            ]);
        }
    }

    /**
     * Handle the Pengeluaran "deleted" event.
     */
    public function deleted(Pengeluaran $pengeluaran): void
    {
        Journal::where('reference_type', Pengeluaran::class)
            ->where('reference_id', $pengeluaran->id)
            ->get()
            ->each->delete();
    }
}
