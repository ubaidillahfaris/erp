<?php

namespace App\Http\Controllers;

use App\Models\CreditNoteItem;
use App\Models\InventoryDisposition;
use App\Models\Warehouse;
use App\Services\DispositionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InventoryDispositionController extends Controller
{
    protected $dispositionService;

    public function __construct(DispositionService $dispositionService)
    {
        $this->dispositionService = $dispositionService;
    }

    /**
     * Display a listing of items in quarantine.
     */
    public function index(Request $request)
    {
        $quarantineWarehouse = Warehouse::where('code', 'WH-QRT')->first();

        // Get CreditNoteItems that are in posted credit notes and haven't been fully disposed
        $items = CreditNoteItem::with(['creditNote', 'product', 'saleItem.unit'])
            ->whereHas('creditNote', function ($query) {
                $query->where('status', 'posted');
            })
            ->get()
            ->map(function ($item) {
                $alreadyDisposed = InventoryDisposition::where('credit_note_item_id', $item->id)->sum('quantity');
                $item->remaining_quarantine_qty = $item->quantity_returned - $alreadyDisposed;

                return $item;
            })
            ->filter(function ($item) {
                return $item->remaining_quarantine_qty > 0;
            })
            ->values();

        $warehouses = Warehouse::where('is_active', true)
            ->where('code', '!=', 'WH-QRT')
            ->get();

        return Inertia::render('Quarantine/Index', [
            'quarantineItems' => $items,
            'warehouses' => $warehouses,
            'quarantineWarehouse' => $quarantineWarehouse,
        ]);
    }

    /**
     * Store a newly created disposition in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'credit_note_item_id' => 'required|exists:credit_note_items,id',
            'action' => 'required|in:restock,repair,write_off',
            'quantity' => 'required|numeric|min:0.0001',
            'to_warehouse_id' => 'required_if:action,restock,repair|nullable|exists:warehouses,id',
            'notes' => 'nullable|string',
        ]);

        $item = CreditNoteItem::findOrFail($validated['credit_note_item_id']);
        $quarantineWarehouse = Warehouse::where('code', 'WH-QRT')->firstOrFail();

        $disposition = InventoryDisposition::create([
            'credit_note_item_id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $validated['quantity'],
            'action' => $validated['action'],
            'from_warehouse_id' => $quarantineWarehouse->id,
            'to_warehouse_id' => $validated['to_warehouse_id'] ?? null,
            'notes' => $validated['notes'],
            'processed_by' => Auth::id(),
            'disposed_at' => now(), // Will be updated in service but good for record
        ]);

        try {
            $this->dispositionService->process($disposition);

            return redirect()->back()->with('success', 'Disposisi berhasil diproses.');
        } catch (\Exception $e) {
            $disposition->delete(); // Rollback creation if service fails

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
