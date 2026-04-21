<?php

namespace App\Observers;

use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Exceptions\MissingCOGSException;
use App\Models\Account;
use App\Models\Payable;
use App\Models\Sale;
use App\Services\JournalService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleObserver
{
    public function __construct(protected JournalService $journalService) {}

    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        /** @var \Carbon\Carbon $tanggal */
        $tanggal = $sale->tanggal;

        // 1. Record Revenue Journal (DISABLED - Journals are now read-only)
        /*
        $sale->journals()->create([
            'tanggal' => $tanggal->format('Y-m-d'),
            'type' => 'debit', // Cash in
            'amount' => $sale->total_amount,
            'category' => 'penjualan',
            'payment_method' => $sale->payment_method,
            'description' => "Penjualan INV-{$sale->invoice_number}",
        ]);
        */

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
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        // Trigger only when status changes to completed
        if ($sale->isDirty('status') && $sale->status === 'completed') {
            // Phase 5C: Auto-compute total COGS from line items
            $sale->loadMissing('items');

            if ($sale->items->isEmpty()) {
                throw new MissingCOGSException("Penjualan tanpa item tidak dapat diselesaikan.");
            }

            $cogsTotal = $sale->items->sum(fn ($item) => $item->cost * $item->qty);
            $cogsCents = (int) round($cogsTotal * 100);

            // Per Decision 5C: Silent update to avoid observer loops
            $sale->updateQuietly(['cogs_amount' => $cogsCents]);

            // Rule: sale.cogs_amount null atau zero -> throw MissingCOGSException (Blocking Safety Net)
            if (($sale->cogs_amount ?? 0) <= 0) {
                throw new MissingCOGSException();
            }

            // Journaling logic (Non-blocking)
            try {
                $this->recordSaleJournals($sale);
            } catch (\Exception $e) {
                Log::error("Sale Double-Entry Journaling failed for Sale [{$sale->id}]: " . $e->getMessage(), [
                    'sale_id' => $sale->id,
                    'exception' => $e
                ]);
            }
        }
    }

    /**
     * Record Revenue and COGS journals.
     */
    protected function recordSaleJournals(Sale $sale): void
    {
        $tanggal = $sale->tanggal;
        $refNumber = sprintf(
            'SALE-%s-%d',
            $tanggal->format('Ymd'),
            $sale->id
        );

        // Atomic transaction for the dual entries
        DB::transaction(function () use ($sale, $tanggal, $refNumber) {
            // 1. Revenue Entry: Debit 1102 (Receiv) vs Credit 4101 (Revenue)
            $receivableAcc = Account::findByCode('1102');
            $revenueAcc = Account::findByCode('4101');
            
            $revenueAmountCents = (int) round((float) $sale->total_amount * 100);
            
            $this->journalService->record(new JournalEntryData(
                items: [
                    new JournalItemData($receivableAcc->id, $revenueAmountCents, 'debit'),
                    new JournalItemData($revenueAcc->id, $revenueAmountCents, 'credit'),
                ],
                tanggal: $tanggal,
                ref_number: "{$refNumber}-REV",
                description: "Revenue Penjualan INV-{$sale->invoice_number}",
                journalable: $sale
            ));

            // 2. COGS Entry: Debit 5101 (COGS) vs Credit 1302 (Finished Goods)
            $cogsAcc = Account::findByCode('5101');
            $finishedGoodsAcc = Account::findByCode('1302');
            
            // Logic: sale->cogs_amount is already BIGINT cents in Database
            $cogsAmountCents = (int) $sale->cogs_amount;

            $this->journalService->record(new JournalEntryData(
                items: [
                    new JournalItemData($cogsAcc->id, $cogsAmountCents, 'debit'),
                    new JournalItemData($finishedGoodsAcc->id, $cogsAmountCents, 'credit'),
                ],
                tanggal: $tanggal,
                ref_number: "{$refNumber}-COGS",
                description: "COGS Penjualan INV-{$sale->invoice_number}",
                journalable: $sale
            ));
        });
    }

    /**
     * Handle the Sale "deleted" event.
     */
    public function deleted(Sale $sale): void
    {
        // Delete associated journals (DISABLED - Journals are now read-only)
        // $sale->journals()->get()->each->delete();

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
