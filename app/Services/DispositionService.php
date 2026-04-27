<?php

namespace App\Services;

use App\Actions\RecordStockMovement;
use App\Models\Account;
use App\Models\InventoryDisposition;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispositionService
{
    protected RecordStockMovement $recordStockMovement;

    public function __construct(RecordStockMovement $recordStockMovement)
    {
        $this->recordStockMovement = $recordStockMovement;
    }

    /**
     * Process an inventory disposition.
     */
    public function process(InventoryDisposition $disposition): bool
    {
        return DB::transaction(function () use ($disposition) {
            $this->validateQuantity($disposition);

            switch ($disposition->action) {
                case 'restock':
                    $this->processRestock($disposition);
                    break;
                case 'repair':
                    $this->processRepair($disposition);
                    break;
                case 'write_off':
                    $this->processWriteOff($disposition);
                    break;
                default:
                    throw new \InvalidArgumentException("Invalid disposition action: {$disposition->action}");
            }

            $disposition->update(['disposed_at' => now()]);

            Log::info("Disposition processed: {$disposition->action} for Product #{$disposition->product_id}, Qty: {$disposition->quantity}");

            return true;
        });
    }

    protected function validateQuantity(InventoryDisposition $disposition): void
    {
        $creditNoteItem = $disposition->creditNoteItem;

        $alreadyDisposed = InventoryDisposition::where('credit_note_item_id', $creditNoteItem->id)
            ->where('id', '!=', $disposition->id)
            ->sum('quantity');

        if (($alreadyDisposed + $disposition->quantity) > $creditNoteItem->quantity_returned) {
            throw new \Exception('Disposition quantity exceeds the originally returned quantity.');
        }
    }

    protected function processRestock(InventoryDisposition $disposition): void
    {
        // 1. Move OUT of Quarantine
        $this->recordStockMovement->handle([
            'product_id' => $disposition->product_id,
            'warehouse_id' => $disposition->from_warehouse_id,
            'unit_id' => $disposition->creditNoteItem->saleItem->unit_id,
            'type' => 'out',
            'quantity' => $disposition->quantity,
            'reference_type' => 'disposition_restock',
            'reference_id' => $disposition->id,
            'condition' => 'quarantine',
        ]);

        // 2. Move IN to Destination Warehouse (as Good condition)
        $this->recordStockMovement->handle([
            'product_id' => $disposition->product_id,
            'warehouse_id' => $disposition->to_warehouse_id,
            'unit_id' => $disposition->creditNoteItem->saleItem->unit_id,
            'type' => 'in',
            'quantity' => $disposition->quantity,
            'reference_type' => 'disposition_restock',
            'reference_id' => $disposition->id,
            'condition' => 'good',
        ]);
    }

    protected function processRepair(InventoryDisposition $disposition): void
    {
        // 1. Move OUT of Quarantine
        $this->recordStockMovement->handle([
            'product_id' => $disposition->product_id,
            'warehouse_id' => $disposition->from_warehouse_id,
            'unit_id' => $disposition->creditNoteItem->saleItem->unit_id,
            'type' => 'out',
            'quantity' => $disposition->quantity,
            'reference_type' => 'disposition_repair',
            'reference_id' => $disposition->id,
            'condition' => 'quarantine',
        ]);

        // 2. Move IN to Destination Warehouse (as Refurbished condition)
        $this->recordStockMovement->handle([
            'product_id' => $disposition->product_id,
            'warehouse_id' => $disposition->to_warehouse_id,
            'unit_id' => $disposition->creditNoteItem->saleItem->unit_id,
            'type' => 'in',
            'quantity' => $disposition->quantity,
            'reference_type' => 'disposition_repair',
            'reference_id' => $disposition->id,
            'condition' => 'refurbished',
        ]);
    }

    protected function processWriteOff(InventoryDisposition $disposition): void
    {
        // 1. Move OUT of Quarantine
        $this->recordStockMovement->handle([
            'product_id' => $disposition->product_id,
            'warehouse_id' => $disposition->from_warehouse_id,
            'unit_id' => $disposition->creditNoteItem->saleItem->unit_id,
            'type' => 'out',
            'quantity' => $disposition->quantity,
            'reference_type' => 'disposition_write_off',
            'reference_id' => $disposition->id,
            'condition' => 'quarantine',
        ]);

        // 2. Record Journal Entry (Loss on Inventory)
        // Assume Finished Goods (1302) and Loss on Inventory (new or existing expense account, we'll use COGS 5101 if no loss account exists for simplicity, or we can look for 'Loss on Inventory').
        $inventoryAccount = Account::where('code', '1302')->first();
        $lossAccount = Account::where('code', '5102')->first() ?? Account::where('code', '5101')->first(); // Fallback to COGS

        if ($inventoryAccount && $lossAccount) {
            $valueLost = $disposition->quantity * $disposition->creditNoteItem->saleItem->cost;

            $journalEntry = JournalEntry::create([
                'date' => now()->format('Y-m-d'),
                'description' => "Inventory Write-off for Disposition #{$disposition->id} (Product: {$disposition->product->name})",
                'reference_type' => 'inventory_disposition',
                'reference_id' => $disposition->id,
            ]);

            // Dr Loss (Expense)
            $journalEntry->items()->create([
                'account_id' => $lossAccount->id,
                'debit' => $valueLost,
                'credit' => 0,
            ]);

            // Cr Inventory (Asset)
            $journalEntry->items()->create([
                'account_id' => $inventoryAccount->id,
                'debit' => 0,
                'credit' => $valueLost,
            ]);
        }
    }
}
