<?php

namespace App\Services;

use App\Actions\RecordStockMovement;
use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Models\Account;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditNoteService
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected JournalService $journalService
    ) {}

    /**
     * Post a credit note: reverse stock, reverse journals, and update payables.
     */
    public function post(CreditNote $creditNote): bool
    {
        if ($creditNote->status !== 'draft') {
            throw new \Exception('Only draft credit notes can be posted.');
        }

        return DB::transaction(function () use ($creditNote) {
            $sale = $creditNote->sale;

            // 1. Validate quantity returned (cumulative check)
            $this->validateReturnQuantities($creditNote);

            // 2. Reverse Stock
            $this->reverseStock($creditNote);

            // 3. Reverse Journal Entries (Proportional)
            $this->recordReversingJournals($creditNote);

            // 4. Reduce Payable (if credit sale)
            $this->reducePayable($creditNote);

            // 5. Update Status
            $creditNote->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            Log::info("Credit Note #{$creditNote->credit_note_number} posted successfully for Sale #{$sale->invoice_number}");

            return true;
        });
    }

    /**
     * Ensure total returned quantity per item doesn't exceed original quantity.
     */
    protected function validateReturnQuantities(CreditNote $creditNote): void
    {
        foreach ($creditNote->items as $item) {
            $originalItem = $item->saleItem;

            // Calculate already returned quantity for this sale item (excluding current draft if being updated)
            $alreadyReturned = CreditNoteItem::where('sale_item_id', $originalItem->id)
                ->whereHas('creditNote', function ($q) use ($creditNote) {
                    $q->where('status', 'posted')
                        ->where('id', '!=', $creditNote->id);
                })
                ->sum('quantity_returned');

            if (($alreadyReturned + $item->quantity_returned) > $originalItem->qty) {
                throw new \Exception("Quantity returned for product {$item->product->name} exceeds original sold quantity.");
            }
        }
    }

    /**
     * Create 'in' stock movements for returned items.
     */
    protected function reverseStock(CreditNote $creditNote): void
    {
        $sale = $creditNote->sale;

        foreach ($creditNote->items as $item) {
            if ($item->quantity_returned <= 0) {
                continue;
            }

            $this->recordStockMovement->handle([
                'product_id' => $item->product_id,
                'warehouse_id' => $sale->warehouse_id,
                'unit_id' => $item->saleItem->unit_id,
                'type' => 'in',
                'quantity' => $item->quantity_returned,
                'reference_type' => 'credit_note',
                'reference_id' => $creditNote->id,
                'notes' => "Return from Credit Note #{$creditNote->credit_note_number}",
            ]);
        }
    }

    /**
     * Record proportional reversing journals for the return.
     */
    protected function recordReversingJournals(CreditNote $creditNote): void
    {
        $sale = $creditNote->sale;
        $totalReturnAmount = (float) $creditNote->total_amount;
        $totalReturnCogs = $creditNote->items->sum(fn ($item) => (float) $item->quantity_returned * (float) $item->saleItem->cost);

        $revenueCents = (int) round($totalReturnAmount * 100);
        $cogsCents = (int) round($totalReturnCogs * 100);

        if ($revenueCents <= 0) {
            return;
        }

        // 1. Revenue Reversal: Dr. 4101 (Revenue) vs Cr. 1101/1102 (Cash/AR)
        $revenueAcc = Account::findByCode('4101');
        $reversalAcc = Account::findByCode($sale->payment_method === 'credit' ? '1102' : '1101');

        $this->journalService->record(new JournalEntryData(
            items: [
                new JournalItemData($revenueAcc->id, $revenueCents, 'debit'),
                new JournalItemData($reversalAcc->id, $revenueCents, 'credit'),
            ],
            date: now(),
            ref_number: "CN-REV-{$creditNote->credit_note_number}",
            description: "Pembalikan Pendapatan via Credit Note #{$creditNote->credit_note_number}",
            journalable: $creditNote
        ));

        // 2. COGS Reversal: Dr. 1302 (Finished Goods) vs Cr. 5101 (COGS)
        if ($cogsCents > 0) {
            $inventoryAcc = Account::findByCode('1302');
            $cogsAcc = Account::findByCode('5101');

            $this->journalService->record(new JournalEntryData(
                items: [
                    new JournalItemData($inventoryAcc->id, $cogsCents, 'debit'),
                    new JournalItemData($cogsAcc->id, $cogsCents, 'credit'),
                ],
                date: now(),
                ref_number: "CN-COGS-{$creditNote->credit_note_number}",
                description: "Pembalikan HPP via Credit Note #{$creditNote->credit_note_number}",
                journalable: $creditNote
            ));
        }
    }

    /**
     * Reduce the associated payable balance for credit sales.
     */
    protected function reducePayable(CreditNote $creditNote): void
    {
        $sale = $creditNote->sale;

        if ($sale->payment_method === 'credit' && $sale->payable) {
            $payable = $sale->payable;
            $returnAmount = (float) $creditNote->total_amount;

            if ($returnAmount > (float) $payable->remaining_amount) {
                // This might happen if they already paid part of the invoice
                // Business rule: If return amount > remaining, we can only reduce remaining to 0.
                // Or throw exception. Prompt says "If payable balance goes negative, throw exception".
                // "Payable balance" usually means remaining amount in this context.
                throw new \Exception('Return amount exceeds remaining receivable balance for this credit sale.');
            }

            $payable->update([
                'principal_amount' => (float) $payable->principal_amount - $returnAmount,
                'total_amount' => (float) $payable->total_amount - $returnAmount,
                'remaining_amount' => (float) $payable->remaining_amount - $returnAmount,
            ]);

            Log::info("Payable #{$payable->id} reduced by {$returnAmount} due to Credit Note #{$creditNote->credit_note_number}");
        }
    }
}
