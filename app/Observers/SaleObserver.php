<?php

namespace App\Observers;

use App\Models\Payable;
use App\Models\Sale;
use Exception;
use Illuminate\Support\Facades\Auth;

class SaleObserver
{
    /**
     * Handle the Sale "created" event.
     */
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

        // 2. Auto-create Receivable for Credit Sales
        if ($sale->payment_method === 'credit') {
            // Check relationship first, fallback to request if not yet created (race condition)
            $customer = $sale->saleCustomer?->customer;
            
            if (!$customer && request()->has('customer_id')) {
                $customer = \App\Models\Customer::find(request()->input('customer_id'));
            }

            if (!$customer) {
                throw new Exception('Credit sale harus ada customer');
            }

            $creditSetting = $customer->creditSetting;

            if (!$creditSetting) {
                throw new Exception('Customer ini belum memiliki izin kredit. Aktifkan kredit di halaman Master Customer.');
            }

            if (!$creditSetting->allow_credit) {
                throw new Exception('Customer tidak diizinkan kredit');
            }

            if ($creditSetting->credit_limit !== null) {
                $outstanding = Payable::where('party_type', 'customer')
                    ->where('party_id', $customer->id)
                    ->where('status', '!=', 'paid')
                    ->withSum('payments', 'amount')
                    ->get()
                    ->sum(fn ($payable) => (float) $payable->total_amount - (float) ($payable->payments_sum_amount ?? 0));

                if (($outstanding + (float) $sale->total_amount) > (float) $creditSetting->credit_limit) {
                    throw new Exception('Melebihi credit limit');
                }
            }

            Payable::create([
                'type' => 'receivable',
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'party_type' => 'customer',
                'party_id' => $customer->id,
                'principal_amount' => $sale->total_amount,
                'total_amount' => $sale->total_amount,
                'total_interest' => 0,
                'status' => 'open',
                'created_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Handle the Sale "deleted" event.
     */
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

        // Delete associated receivables
        Payable::where('reference_type', 'sale')
            ->where('reference_id', $sale->id)
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
