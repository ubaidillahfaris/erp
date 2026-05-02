<?php

namespace App\Services;

use App\Actions\RecordStockMovement;
use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\StockMovement;
use App\Models\StockOpname;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StornoService
{
    public function __construct(
        protected RecordStockMovement $recordStockMovement,
        protected JournalService $journalService
    ) {}

    /**
     * Perform storno (reversal) for a given model.
     *
     * @param  Model  $model  The transaction model to be stornoed
     * @param  string|null  $reason  Reason for the storno
     *
     * @throws \Exception
     */
    public function perform(Model $model, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($model, $reason) {
            // 1. Accounting Reversal (Double Entry)
            $this->reverseJournalEntries($model, $reason);

            // 2. Module Specific Logic (Stock, Status)
            if ($model instanceof StockOpname) {
                return $this->stornoStockOpname($model, $reason);
            }

            if ($model instanceof Sale) {
                return $this->stornoSale($model, $reason);
            }

            if ($model instanceof ServiceOrder) {
                return $this->stornoServiceOrder($model, $reason);
            }

            // Future modules can be added here (Purchasing, etc.)

            throw new \Exception('Model '.get_class($model).' does not support storno yet.');
        });
    }

    /**
     * Reverse all journal entries associated with the given model.
     */
    protected function reverseJournalEntries(Model $model, ?string $reason): void
    {
        $originalEntries = JournalEntry::where('journalable_type', get_class($model))
            ->where('journalable_id', $model->id)
            ->with('items')
            ->get();

        foreach ($originalEntries as $entry) {
            $reverseItems = [];
            foreach ($entry->items as $item) {
                // Swap Debit and Credit
                $reverseItems[] = new JournalItemData(
                    account_id: $item->account_id,
                    amount: $item->debit ?: $item->credit,
                    type: $item->debit ? 'credit' : 'debit'
                );
            }

            $this->journalService->record(new JournalEntryData(
                items: $reverseItems,
                date: now(),
                description: 'STORNO: '.($reason ?: 'Cancellation of '.$entry->description),
                journalable: $model,
                ref_number: 'STRN-'.$entry->ref_number
            ));
        }
    }

    /**
     * Specific logic for stornoing a Stock Opname.
     */
    protected function stornoStockOpname(StockOpname $opname, ?string $reason): bool
    {
        if ($opname->status !== 'completed') {
            throw new \Exception("Only Stock Opname with 'completed' status can be reversed.");
        }

        // 1. Reverse Stock Movements
        $this->reverseStockMovements($opname, $reason);

        // 2. Update Model Status and Audit information
        $opname->update([
            'status' => 'storno',
            'storno_at' => now(),
            'storno_reason' => $reason,
        ]);

        Log::info("Storno performed for StockOpname #{$opname->id}");

        return true;
    }

    /**
     * Specific logic for voiding a Sale transaction.
     */
    protected function stornoSale(Sale $sale, ?string $reason): bool
    {
        if ($sale->status !== 'completed') {
            throw new \Exception("Only sale transactions with 'completed' status can be voided.");
        }

        // 1. Reverse Stock by creating 'in' movements for all items
        $this->reverseSaleStock($sale, $reason);

        // 2. Update Sale Status
        $sale->update([
            'status' => 'voided',
            'storno_at' => now(),
            'storno_reason' => $reason,
        ]);

        Log::info("Void performed for Sale #{$sale->invoice_number}");

        return true;
    }

    /**
     * Reverse stock for a sale transaction.
     */
    protected function reverseSaleStock(Sale $sale, ?string $reason): void
    {
        foreach ($sale->items as $item) {
            $this->recordStockMovement->handle([
                'product_id' => $item->product_id,
                'unit_id' => $item->unit_id,
                'type' => 'in', // Return to stock
                'quantity' => $item->qty,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'notes' => 'STORNO: '.($reason ?: "Cancellation of sale #{$sale->invoice_number}"),
            ]);
        }
    }

    /**
     * Generic logic to reverse all stock movements associated with a model.
     * (Currently used by StockOpname)
     */
    protected function reverseStockMovements(Model $model, ?string $reason): void
    {
        $movements = StockMovement::where('reference_type', 'stock_opname')
            ->where('reference_id', $model->id)
            ->get();

        foreach ($movements as $movement) {
            // Create a counter-movement (In -> Out, Out -> In)
            $this->recordStockMovement->handle([
                'product_id' => $movement->product_id,
                'unit_id' => $movement->unit_id,
                'type' => $movement->type === 'in' ? 'out' : 'in',
                'quantity' => $movement->quantity,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'notes' => 'STORNO: '.($reason ?: "Cancellation of transaction #{$model->id}"),
            ]);
        }
    }

    /**
     * Specific logic for voiding a Service Order.
     */
    protected function stornoServiceOrder(ServiceOrder $order, ?string $reason): bool
    {
        if ($order->status === 'cancelled') {
            return true;
        }

        // Refund payments by creating negative payments
        foreach ($order->payments as $payment) {
            $order->payments()->create([
                'company_id' => $order->company_id,
                'payment_date' => now()->toDateString(),
                'payment_method' => $payment->payment_method,
                'amount' => -$payment->amount,
                'notes' => 'STORNO: '.($reason ?: 'Order cancelled'),
                'created_by' => auth()->id(),
            ]);
        }

        $order->update([
            'status' => 'cancelled',
            'total_paid' => 0,
            'storno_at' => now(),
            'storno_reason' => $reason,
        ]);

        Log::info("Void performed for Service Order #{$order->order_number}");

        return true;
    }
}
