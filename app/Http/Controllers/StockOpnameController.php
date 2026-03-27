<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Actions\RecordStockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function index(Request $request): Response
    {
        $query = StockOpname::withCount('items');

        if ($request->has('search') && !empty($request->search)) {
            $query->where('keterangan', 'like', "%{$request->search}%");
        }

        $perPage = $request->input('per_page', 10);

        return Inertia::render('stock-opname/Index', [
            'opnames' => $query->latest('tanggal')->latest('id')->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function create(Request $request): Response
    {
        $query = Produk::with(['stock', 'satuan']);

        if ($request->has('search') && !empty($request->search)) {
            $query->where('nama', 'like', "%{$request->search}%");
        }

        $produks = $query->paginate(10)->withQueryString();
        
        return Inertia::render('stock-opname/Create', [
            'produks' => $produks,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:draft,completed',
            'items' => 'required|array',
            'items.*.produk_id' => 'required|exists:produks,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.system_qty' => 'required|numeric',
            'items.*.physical_qty' => 'required|numeric',
        ]);

        DB::transaction(function () use ($validated) {
            $opname = StockOpname::create([
                'tanggal' => $validated['tanggal'],
                'keterangan' => $validated['keterangan'] ?? null,
                'status' => $validated['status'],
            ]);

            foreach ($validated['items'] as $item) {
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
        $stockOpname->load(['items.produk.satuan', 'items.satuan']);

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
        
        $query = Produk::with(['stock', 'satuan']);

        if ($request->has('search') && !empty($request->search)) {
            $query->where('nama', 'like', "%{$request->search}%");
        }

        $produks = $query->paginate(10)->withQueryString();

        return Inertia::render('stock-opname/Edit', [
            'opname' => $stockOpname,
            'produks' => $produks,
            'filters' => $request->only(['search']),
        ]);
    }

    public function update(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status === 'completed') {
            return redirect()->route('stock-opname.show', $stockOpname);
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:draft,completed',
            'items' => 'required|array',
            'items.*.produk_id' => 'required|exists:produks,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.system_qty' => 'required|numeric',
            'items.*.physical_qty' => 'required|numeric',
        ]);

        DB::transaction(function () use ($validated, $stockOpname) {
            $stockOpname->update([
                'tanggal' => $validated['tanggal'],
                'keterangan' => $validated['keterangan'] ?? null,
                'status' => $validated['status'],
            ]);

            $stockOpname->items()->delete();
            foreach ($validated['items'] as $item) {
                $stockOpname->items()->create($item);
            }

            if ($validated['status'] === 'completed') {
                $stockOpname->refresh();
                $this->finalizeOpname($stockOpname);
            }
        });

        return redirect()->route('stock-opname.index')->with('success', 'Stock opname berhasil diperbarui.');
    }

    public function destroy(StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status === 'completed') {
            return redirect()->back()->with('error', 'Stock opname yang sudah selesai tidak dapat dihapus.');
        }

        $stockOpname->delete();

        return redirect()->route('stock-opname.index')->with('success', 'Stock opname berhasil dihapus.');
    }

    private function finalizeOpname(StockOpname $opname): void
    {
        foreach ($opname->items as $item) {
            $diff = (float) $item->physical_qty - (float) $item->system_qty;
            if (abs($diff) > 0.000001) {
                app(RecordStockMovement::class)->handle([
                    'produk_id' => $item->produk_id,
                    'satuan_id' => $item->satuan_id,
                    'type' => $diff > 0 ? 'in' : 'out',
                    'jumlah' => abs($diff),
                    'reference_type' => 'stock_opname',
                    'reference_id' => $opname->id,
                    'keterangan' => 'Penyesuaian stok dari Opname #' . $opname->id . ' tgl ' . ($opname->tanggal instanceof \Carbon\Carbon ? $opname->tanggal->format('d/m/Y') : $opname->tanggal),
                ]);
            }
        }
    }
}
