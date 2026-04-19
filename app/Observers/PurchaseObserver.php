<?php

namespace App\Observers;

use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Exceptions\InvalidPurchaseAmountException;
use App\Models\Account;
use App\Models\Purchase;
use App\Services\JournalService;
use Illuminate\Support\Facades\Log;

class PurchaseObserver
{
    public function __construct(protected JournalService $journalService) {}

    /**
     * Handle the Purchase "updated" event.
     */
    public function updated(Purchase $purchase): void
    {
        // Trigger only when status changes to finalized
        if ($purchase->isDirty('status') && $purchase->status === 'finalized') {
            try {
                $this->recordJournal($purchase);
            } catch (\Exception $e) {
                // Decision Phase 3/5A: Non-blocking failures
                Log::error("Double-Entry Journaling failed for Purchase [{$purchase->id}]: " . $e->getMessage(), [
                    'purchase_id' => $purchase->id,
                    'exception' => $e
                ]);
            }
        }
    }

    /**
     * Record double-entry journal for finalized purchase.
     * Journal accounts depend on payment_method:
     *   cash / transfer  → Dr. Persediaan / Cr. Kas & Bank (no Payable created)
     *   credit           → Dr. Persediaan / Cr. Hutang Usaha + create Payable
     */
    protected function recordJournal(Purchase $purchase): void
    {
        $amount = (float) ($purchase->total_biaya ?? 0);

        if ($amount <= 0) {
            throw new InvalidPurchaseAmountException();
        }

        $tanggal = $purchase->tanggal;

        $refNumber = sprintf(
            'PUR-%s-%d',
            $tanggal->format('Ymd'),
            $purchase->id
        );

        $vendorName = $purchase->vendor?->nama ?? 'Unknown Vendor';
        $paymentMethod = $purchase->payment_method ?? 'cash';

        // Debit: Persediaan Bahan Baku always
        $rawMaterialAcc = Account::findByCode('1301');

        // Credit: depends on payment method
        $creditAcc = match ($paymentMethod) {
            'credit' => Account::findByCode('2101'), // Hutang Usaha
            default  => Account::findByCode('1101'), // Kas & Bank
        };

        $description = match ($paymentMethod) {
            'credit'   => "Pembelian kredit dari vendor: {$vendorName}",
            'transfer' => "Pembelian via transfer dari vendor: {$vendorName}",
            default    => "Pembelian tunai dari vendor: {$vendorName}",
        };

        $amountCents = (int) round($amount * 100);

        $journalData = new JournalEntryData(
            items: [
                new JournalItemData(
                    account_id: $rawMaterialAcc->id,
                    amount: $amountCents,
                    type: 'debit'
                ),
                new JournalItemData(
                    account_id: $creditAcc->id,
                    amount: $amountCents,
                    type: 'credit'
                ),
            ],
            tanggal: $tanggal,
            ref_number: $refNumber,
            description: $description,
            journalable: $purchase
        );

        $this->journalService->record($journalData);

        // Only create Payable for credit purchases
        if ($paymentMethod === 'credit' && $purchase->vendor_id) {
            \App\Models\Payable::firstOrCreate(
                ['reference_type' => 'purchase', 'reference_id' => $purchase->id],
                [
                    'type'             => 'payable',
                    'party_type'       => 'vendor',
                    'party_id'         => $purchase->vendor_id,
                    'principal_amount' => $amount,
                    'total_amount'     => $amount,
                    'total_interest'   => 0,
                    'paid_amount'      => 0,
                    'remaining_amount' => $amount,
                    'status'           => 'open',
                    'created_by'       => auth()->id(),
                ]
            );
        }
    }
}
