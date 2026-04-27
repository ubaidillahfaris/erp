<?php

namespace App\Http\Controllers;

use App\Actions\RecordStockMovement;
use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Models\Account;
use App\Models\Product;
use App\Models\ProductPriceStat;
use App\Models\StockOpname;
use App\Services\JournalService;
use App\Services\StornoService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function __construct(
        protected StornoService $stornoService
    ) {}

    public function index(Request $request): Response
    {
        $query = StockOpname::withCount('items');

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('notes', 'like', "%{$request->search}%");
        }

        $perPage = $request->input('per_page', 10);

        return Inertia::render('stock-opname/Index', [
            'opnames' => $query->latest('date')->latest('id')->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function create(Request $request): Response
    {
        $query = Product::with(['stock', 'unit']);

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $products = $query->paginate(10)->withQueryString();

        return Inertia::render('stock-opname/Create', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,completed',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.system_qty' => 'required|numeric',
            'items.*.physical_qty' => 'required|numeric',
            'items.*.unit_price' => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($validated) {
            $opname = StockOpname::create([
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);

            foreach ($validated['items'] as &$item) {
                if (! isset($item['unit_price']) || $item['unit_price'] === null) {
                    $avgPrice = ProductPriceStat::where('product_id', $item['product_id'])->value('avg_price');
                    if ($avgPrice === null) {
                        Log::warning("unit_price not found for product_id {$item['product_id']}");
                        $item['unit_price'] = 0;
                    } else {
                        $item['unit_price'] = (int) round(((float) $avgPrice) * 100);
                    }
                } else {
                    $item['unit_price'] = (int) round(((float) $item['unit_price']) * 100);
                }
                $opname->items()->create($item);
            }

            if ($validated['status'] === 'completed') {
                $opname->refresh();
                $this->finalizeOpname($opname);
            }
        });

        return redirect()->route('stock-opname.index')->with('success', 'Stock opname berhasil disimpan.');
    }

    public function show(StockOpname $stockOpname): Response
    {
        $stockOpname->load(['items.product.unit', 'items.unit']);

        return Inertia::render('stock-opname/Show', [
            'opname' => $stockOpname,
        ]);
    }

    public function edit(Request $request, StockOpname $stockOpname): Response|RedirectResponse
    {
        if ($stockOpname->status === 'completed') {
            return redirect()->route('stock-opname.show', $stockOpname);
        }

        $stockOpname->load('items');

        $query = Product::with(['stock', 'unit']);

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $products = $query->paginate(10)->withQueryString();

        return Inertia::render('stock-opname/Edit', [
            'opname' => $stockOpname,
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }

    public function update(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status === 'completed') {
            return redirect()->route('stock-opname.show', $stockOpname);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,completed',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.system_qty' => 'required|numeric',
            'items.*.physical_qty' => 'required|numeric',
            'items.*.unit_price' => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($validated, $stockOpname) {
            $stockOpname->update([
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);

            $stockOpname->items()->delete();
            foreach ($validated['items'] as &$item) {
                if (! isset($item['unit_price']) || $item['unit_price'] === null) {
                    $avgPrice = ProductPriceStat::where('product_id', $item['product_id'])->value('avg_price');
                    if ($avgPrice === null) {
                        Log::warning("unit_price not found for product_id {$item['product_id']}");
                        $item['unit_price'] = 0;
                    } else {
                        $item['unit_price'] = (int) round(((float) $avgPrice) * 100);
                    }
                } else {
                    $item['unit_price'] = (int) round(((float) $item['unit_price']) * 100);
                }
                $stockOpname->items()->create($item);
            }

            if ($validated['status'] === 'completed') {
                $stockOpname->refresh();
                $this->finalizeOpname($stockOpname);
            }
        });

        return redirect()->route('stock-opname.index')->with('success', 'Stock opname updated successfully.');
    }

    public function destroy(StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status === 'completed') {
            return redirect()->back()->with('error', 'Stock opname yang sudah selesai tidak dapat dihapus.');
        }

        $stockOpname->delete();

        return redirect()->route('stock-opname.index')->with('success', 'Stock opname deleted successfully.');
    }

    public function stornoOpname(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'nullable|string|max:255',
            ]);

            Log::info("Performing Storno for OPN-{$stockOpname->id}");
            $this->stornoService->perform($stockOpname, $validated['reason'] ?? 'Dibatalkan oleh pengguna');

            return redirect()->back()->with('success', 'Hasil opname berhasil di-storno (reversal).');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal melakukan storno: '.$e->getMessage());
        }
    }

    public function reopen(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        try {
            DB::transaction(function () use ($stockOpname) {
                // 1. Perform reversal (storno)
                $this->stornoService->perform($stockOpname, 'Dibuka kembali untuk pengeditan');

                // 2. Set back to draft
                $stockOpname->update([
                    'status' => 'draft',
                    'storno_at' => null,
                    'storno_reason' => null,
                ]);
            });

            return redirect()->route('stock-opname.edit', $stockOpname)->with('success', 'Opname telah dikembalikan ke Draft untuk diedit.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuka kembali: '.$e->getMessage());
        }
    }

    private function finalizeOpname(StockOpname $opname): void
    {
        foreach ($opname->items as $item) {
            $diff = (float) $item->physical_qty - (float) $item->system_qty;
            if (abs($diff) > 0.000001) {
                app(RecordStockMovement::class)->handle([
                    'product_id' => $item->product_id,
                    'unit_id' => $item->unit_id,
                    'type' => $diff > 0 ? 'in' : 'out',
                    'quantity' => abs($diff),
                    'reference_type' => 'stock_opname',
                    'reference_id' => $opname->id,
                    'notes' => 'Penyesuaian stok dari Opname #'.$opname->id.' tgl '.($opname->date instanceof Carbon ? $opname->date->format('d/m/Y') : $opname->date),
                ]);

                // Journal Logic
                try {
                    $diffQty = (float) $item->physical_qty - (float) $item->system_qty;
                    $priceUnit = (int) ($item->unit_price ?? 0);
                    $nilaiSelisih = (int) round(abs($diffQty) * $priceUnit);

                    if ($nilaiSelisih > 0) {
                        $journalService = app(JournalService::class);
                        $refNumber = 'OPN-'.($opname->date instanceof Carbon ? $opname->date->format('Ymd') : date('Ymd', strtotime((string) $opname->date)))."-{$opname->id}-{$item->id}";

                        $persediaanAccount = Account::where('code', '1301')->first();
                        $itemsData = [];

                        if ($diffQty > 0) {
                            // Surplus
                            $incomeAccount = Account::where('code', '4102')->first();
                            if ($persediaanAccount && $incomeAccount) {
                                $itemsData = [
                                    new JournalItemData($persediaanAccount->id, $nilaiSelisih, 'debit'),
                                    new JournalItemData($incomeAccount->id, $nilaiSelisih, 'credit'),
                                ];
                            }
                        } else {
                            // Shrinkage
                            $expenseAccount = Account::where('code', '6201')->first();
                            if ($persediaanAccount && $expenseAccount) {
                                $itemsData = [
                                    new JournalItemData($expenseAccount->id, $nilaiSelisih, 'debit'),
                                    new JournalItemData($persediaanAccount->id, $nilaiSelisih, 'credit'),
                                ];
                            }
                        }

                        if (! empty($itemsData)) {
                            $journalData = new JournalEntryData(
                                items: $itemsData,
                                description: "Penyesuaian stok Opname #{$opname->id} item {$item->product_id}",
                                date: $opname->date,
                                ref_number: $refNumber,
                                journalable: $opname
                            );
                            $journalService->record($journalData);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to record journal for Opname item #{$item->id}: ".$e->getMessage());
                }
            }
        }
    }
}
