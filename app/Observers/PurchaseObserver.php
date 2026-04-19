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
     */
    protected function recordJournal(Purchase $purchase): void
    {
        $amount = (float) ($purchase->total_biaya ?? 0);

        if ($amount <= 0) {
            throw new InvalidPurchaseAmountException();
        }

        $tanggal = $purchase->tanggal; // Date instance from casts

        $refNumber = sprintf(
            'PUR-%s-%d',
            $tanggal->format('Ymd'),
            $purchase->id
        );

        $vendorName = $purchase->vendor?->nama ?? 'Unknown Vendor';
        $description = "Pembelian stok dari vendor: {$vendorName}";

        // Accounts: Debit 1301 (Raw materials) vs Credit 2101 (Payables)
        $rawMaterialAcc = Account::findByCode('1301');
        $payableAcc = Account::findByCode('2101');

        if (!$rawMaterialAcc || !$payableAcc) {
            throw new \Exception("Required accounts (1301 or 2101) not found in Chart of Accounts.");
        }

        $amountCents = (int) round($amount * 100);

        $journalData = new JournalEntryData(
            items: [
                new JournalItemData(
                    account_id: $rawMaterialAcc->id,
                    amount: $amountCents,
                    type: 'debit'
                ),
                new JournalItemData(
                    account_id: $payableAcc->id,
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
    }
}
