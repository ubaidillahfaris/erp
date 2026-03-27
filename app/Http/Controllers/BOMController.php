<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBOMRequest;
use App\Http\Requests\UpdateBOMRequest;
use App\Models\Bom;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BOMController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        $query = Bom::with('produk.satuan', 'produk.currentPrice')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('nama', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhereHas('produk', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%");
                });
        }

        if ($request->wantsJson()) {
            $query->with(['items.produk.satuan', 'items.satuan']);
            return response()->json($query->paginate(10));
        }

        $perPage = $request->input('per_page', 10);
        $boms = $query->paginate($perPage)->withQueryString();

        return Inertia::render('bom/Index', [
            'boms' => $boms,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $produks = Produk::whereIn('type', ['finished_good', 'intermediate_good'])
            ->whereDoesntHave('bom')
            ->get();

        $bahanBakus = Produk::with(['satuan', 'currentPrice'])->whereIn('type', ['raw_material', 'intermediate_good'])->get();

        $satuans = \App\Models\Satuan::all();

        return Inertia::render('bom/Create', [
            'produks' => $produks,
            'bahanBakus' => $bahanBakus,
            'satuans' => $satuans,
            'conversions' => \App\Models\SatuanConversion::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBOMRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();

            if (empty($data['sku'])) {
                // Generate a unique SKU (BOM-0001, etc)
                $latestBom = Bom::latest('id')->first();
                $nextId = $latestBom ? $latestBom->id + 1 : 1;
                $data['sku'] = 'BOM-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }

            $bom = Bom::create($data);

            foreach ($request->items as $item) {
                $bom->items()->create($item);
            }

            // Recalculate HPP for the product
            app(\App\Actions\RecalculateHpp::class)->handle($bom->produk);
        });

        return redirect()->route('bom.index')->with('success', 'BOM berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bom $bom): Response
    {
        $bom->load('items.produk.satuan');

        $produks = Produk::whereIn('type', ['finished_good', 'intermediate_good'])
            ->where(function ($query) use ($bom) {
                $query->whereDoesntHave('bom')
                    ->orWhere('id', $bom->produk_id);
            })
            ->get();

        $bahanBakus = Produk::with(['satuan', 'currentPrice'])->whereIn('type', ['raw_material', 'intermediate_good'])->get();

        $satuans = \App\Models\Satuan::all();

        // Get latest production yield if it exists
        $latestProduction = \App\Models\Production::where('bom_id', $bom->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        $latestProductionYield = $latestProduction ? (float) $latestProduction->actual_yield : null;

        return Inertia::render('bom/Edit', [
            'bom' => $bom,
            'produks' => $produks,
            'bahanBakus' => $bahanBakus,
            'satuans' => $satuans,
            'conversions' => \App\Models\SatuanConversion::all(),
            'latest_production_yield' => $latestProductionYield,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBOMRequest $request, Bom $bom): RedirectResponse
    {
        DB::transaction(function () use ($request, $bom) {
            $bom->update($request->validated());

            $bom->items()->delete();
            foreach ($request->items as $item) {
                $bom->items()->create($item);
            }

            // Recalculate HPP for the product
            app(\App\Actions\RecalculateHpp::class)->handle($bom->produk);
        });

        return redirect()->route('bom.index')->with('success', 'BOM berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bom $bom): RedirectResponse
    {
        $bom->delete();

        return redirect()->route('bom.index')->with('success', 'BOM berhasil dihapus.');
    }
}
