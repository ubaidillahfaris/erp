<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\StockMovement;
use App\Models\Satuan;
use App\Actions\RecordStockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class StockController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Produk::query()
            ->whereIn('type', ['raw_material', 'intermediate_good'])
            ->with(['stock', 'satuan'])
            ->withCount('stockMovements');
        $perPage = $request->input('per_page', 10);

        if ($request->has('search') && !empty($request->search)) {
            $query->where('nama', 'like', "%{$request->search}%")
                ->orWhere('sku', 'like', "%{$request->search}%");
        }

        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        $produks = $query->paginate($perPage)->withQueryString();
        $satuans = Satuan::all();
        $conversions = \App\Models\SatuanConversion::all();

        return Inertia::render('stock/Index', [
            'produks' => $produks,
            'satuans' => $satuans,
            'conversions' => $conversions,
            'filters' => $request->only(['search', 'type', 'per_page']),
        ]);
    }

    public function show(Produk $produk, Request $request): Response
    {
        $produk->load(['stock', 'satuan']);

        $perPage = $request->input('per_page', 10);

        $movements = StockMovement::where('produk_id', $produk->id)
            ->with('satuan')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('stock/Show', [
            'produk' => $produk,
            'movements' => $movements,
            'filters' => $request->only(['per_page']),
        ]);
    }

    public function adjustment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'satuan_id' => 'required|exists:satuans,id',
            'type' => 'sometimes|in:in,out',
            'jumlah' => 'sometimes|numeric',
            'physical_qty' => 'sometimes|numeric',
            'keterangan' => 'required|string|max:255',
        ]);

        if ($request->has('physical_qty')) {
            $produk = Produk::with('stock')->findOrFail($request->produk_id);
            $currentBalance = (float) ($produk->stock->balance ?? 0);

            // Convert physical_qty to base unit
            $ratio = app(\App\Services\SatuanService::class)->getConversionRatio($produk->satuan_id, $request->satuan_id);
            $physicalQtyBase = (float) $request->physical_qty / ($ratio ?: 1);

            $diff = $physicalQtyBase - $currentBalance;

            if (abs($diff) < 0.000001) {
                return redirect()->back()->with('success', 'Stok sudah sesuai, tidak ada penyesuaian.');
            }

            $validated['type'] = $diff > 0 ? 'in' : 'out';
            $validated['jumlah'] = abs($diff) * ($ratio ?: 1);
        }

        app(RecordStockMovement::class)->handle(array_merge($validated, [
            'reference_type' => 'adjustment',
            'reference_id' => auth()->id(),
        ]));

        return redirect()->back()->with('success', 'Penyesuaian stok berhasil disimpan.');
    }
}
