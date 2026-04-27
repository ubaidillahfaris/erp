<?php

namespace App\Services;

use App\Actions\RecordStockMovement;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    /**
     * Dispatch the stock transfer: deduct stock from source warehouse and set status to in_transit.
     */
    public function dispatch(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $transfer->load('items');

            foreach ($transfer->items as $item) {
                app(RecordStockMovement::class)->handle([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'unit_id' => $item->unit_id,
                    'type' => 'out',
                    'quantity' => $item->quantity_requested,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'notes' => "Stock Transfer Dispatch: {$transfer->transfer_number}",
                ]);
            }

            $transfer->update([
                'status' => 'in_transit',
                'transferred_at' => now(),
            ]);
        });
    }

    /**
     * Receive the stock transfer: add stock to destination warehouse and set status to completed.
     */
    public function receive(StockTransfer $transfer, array $receivedQuantities): void
    {
        DB::transaction(function () use ($transfer, $receivedQuantities) {
            $transfer->load('items');

            foreach ($transfer->items as $item) {
                $receivedQty = $receivedQuantities[$item->id] ?? $item->quantity_requested;

                $item->update(['quantity_received' => $receivedQty]);

                app(RecordStockMovement::class)->handle([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'unit_id' => $item->unit_id,
                    'type' => 'in',
                    'quantity' => $receivedQty,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'notes' => "Stock Transfer Receipt: {$transfer->transfer_number}",
                ]);
            }

            $transfer->update(['status' => 'completed']);
        });
    }

    /**
     * Cancel the stock transfer.
     */
    public function cancel(StockTransfer $transfer, string $reason = ''): void
    {
        DB::transaction(function () use ($transfer, $reason) {
            if ($transfer->status === 'in_transit') {
                $transfer->load('items');

                // Reverse the dispatch: add back to source warehouse
                foreach ($transfer->items as $item) {
                    app(RecordStockMovement::class)->handle([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $transfer->from_warehouse_id,
                        'unit_id' => $item->unit_id,
                        'type' => 'in',
                        'quantity' => $item->quantity_requested,
                        'reference_type' => 'stock_transfer_cancel',
                        'reference_id' => $transfer->id,
                        'notes' => "Stock Transfer Cancel: {$transfer->transfer_number}. Reason: {$reason}",
                    ]);
                }
            }

            $transfer->update([
                'status' => 'cancelled',
                'notes' => trim($transfer->notes."\nCancelled: ".$reason),
            ]);
        });
    }
}
