<?php

namespace App\Observers;

use App\Models\Sale;

class SaleObserver
{
    public function created(Sale $sale): void
    {
        /** @var \Carbon\Carbon $tanggal */
        $tanggal = $sale->tanggal;

        // 1. Record Revenue Journal (Total amount is available here)
        $sale->journals()->create([
            'tanggal' => $tanggal->format('Y-m-d'),
            'type' => 'debit', // Cash in
            'amount' => $sale->total_amount,
            'category' => 'penjualan',
            'payment_method' => $sale->payment_method,
            'description' => "Penjualan INV-{$sale->invoice_number}",
        ]);
    }

    public function deleted(Sale $sale): void
    {
        // Delete associated journals
        $sale->journals()->get()->each->delete();

        // Delete associated stock movements
        \App\Models\StockMovement::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->get()
            ->each
            ->delete();
    }

    /**
     * Handle the Sale "restored" event.
     */
    public function restored(Sale $sale): void
    {
        //
    }

    /**
     * Handle the Sale "force deleted" event.
     */
    public function forceDeleted(Sale $sale): void
    {
        //
    }
}
