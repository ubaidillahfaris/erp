<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\Sale;
use App\Services\CreditNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CreditNoteController extends Controller
{
    public function __construct(protected CreditNoteService $creditNoteService) {}

    public function index(): Response
    {
        $creditNotes = CreditNote::with(['sale', 'creator'])
            ->latest()
            ->paginate(10);

        return Inertia::render('CreditNotes/Index', [
            'creditNotes' => $creditNotes,
        ]);
    }

    public function createGeneral(): Response
    {
        return $this->create();
    }

    public function create(?Sale $sale = null): Response
    {
        if ($sale) {
            $sale->load(['items.product', 'items.unit', 'saleCustomer.customer']);

            // Calculate returnable quantities
            foreach ($sale->items as $item) {
                $returnedQty = $item->creditNoteItems()
                    ->whereHas('creditNote', fn ($q) => $q->where('status', 'posted'))
                    ->sum('quantity_returned');

                $item->returnable_qty = max(0, $item->qty - $returnedQty);
            }
        }

        return Inertia::render('CreditNotes/Create', [
            'sale' => $sale,
        ]);
    }

    public function getSaleDetails(Sale $sale)
    {
        $sale->load(['items.product', 'items.unit', 'saleCustomer.customer']);

        // Calculate returnable quantities
        foreach ($sale->items as $item) {
            $returnedQty = $item->creditNoteItems()
                ->whereHas('creditNote', fn ($q) => $q->where('status', 'posted'))
                ->sum('quantity_returned');

            $item->returnable_qty = max(0, $item->qty - $returnedQty);
        }

        return response()->json($sale);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'reason' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity_returned' => 'required|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $sale = Sale::findOrFail($validated['sale_id']);

                // Generate Credit Note Number
                $cnNumber = 'CN-'.now()->format('ymd').strtoupper(bin2hex(random_bytes(2)));

                $creditNote = CreditNote::create([
                    'credit_note_number' => $cnNumber,
                    'sale_id' => $sale->id,
                    'status' => 'draft',
                    'reason' => $validated['reason'],
                    'created_by' => auth()->id(),
                    'total_amount' => 0, // Will update after items
                ]);

                $totalAmount = 0;
                foreach ($validated['items'] as $itemData) {
                    if ($itemData['quantity_returned'] <= 0) {
                        continue;
                    }

                    $saleItem = $sale->items()->findOrFail($itemData['sale_item_id']);

                    // Basic validation here as well
                    $returnedSoFar = $saleItem->creditNoteItems()
                        ->whereHas('creditNote', fn ($q) => $q->where('status', 'posted'))
                        ->sum('quantity_returned');

                    if ($itemData['quantity_returned'] > ($saleItem->qty - $returnedSoFar)) {
                        throw new \Exception("Quantity returned for item {$saleItem->id} exceeds limit.");
                    }

                    $subtotal = $itemData['quantity_returned'] * (float) $saleItem->price;
                    $totalAmount += $subtotal;

                    $creditNote->items()->create([
                        'sale_item_id' => $saleItem->id,
                        'product_id' => $saleItem->product_id,
                        'quantity_returned' => $itemData['quantity_returned'],
                        'unit_price' => $saleItem->price,
                        'subtotal' => $subtotal,
                    ]);
                }

                if ($totalAmount <= 0) {
                    throw new \Exception('At least one item must be returned with quantity > 0.');
                }

                $creditNote->update(['total_amount' => $totalAmount]);

                return redirect()->route('credit-notes.show', $creditNote->id)
                    ->with('success', 'Nota Kredit berhasil dibuat sebagai draft.');
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(CreditNote $creditNote): Response
    {
        $creditNote->load(['sale.saleCustomer.customer', 'items.product', 'items.saleItem.unit', 'creator']);

        return Inertia::render('CreditNotes/Show', [
            'creditNote' => $creditNote,
        ]);
    }

    public function post(CreditNote $creditNote)
    {
        try {
            $this->creditNoteService->post($creditNote);

            return redirect()->route('credit-notes.show', $creditNote->id)
                ->with('success', 'Nota Kredit berhasil diposting dan jurnal telah dicatat.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
