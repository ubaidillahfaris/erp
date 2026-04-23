<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProdukRequest;
use App\Http\Requests\UpdateProdukRequest;
use App\Models\Category;
use App\Models\Produk;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        // Handle faceted filters (from DataTable) or legacy 'jenis'
        $activeFilters = $request->input('active_filters', []);
        $jenis = $activeFilters['jenis'] ?? $request->input('jenis');

        $sort = $request->input('sort') ?: 'created_at';
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Base query with virtual 'stok' column from stocks table
        $query = Produk::query()
            ->with(['satuan', 'currentPrice', 'category'])
            ->addSelect(['stok' => \App\Models\Stock::select('balance')
                ->whereColumn('produk_id', 'produks.id')
                ->limit(1)
            ])
            ->when($jenis && $jenis !== 'all', function ($query) use ($jenis) {
                if (is_array($jenis)) {
                    $query->whereIn('type', $jenis);
                } else {
                    $query->where('type', $jenis);
                }
            });

        if ($request->filled('search')) {
            $produks = Produk::search($search)
                ->query(fn ($q) => $q->mergeConstraintsFrom($query)->orderBy($sort, $direction))
                ->paginate($perPage);
        } else {
            $produks = $query->orderBy($sort, $direction)
                ->paginate($perPage);
        }

        return Inertia::render('produk/Index', [
            'produks' => $produks->withQueryString(),
            'filters' => $request->only(['search', 'active_filters', 'per_page', 'sort', 'direction']),
        ]);
    }

    public function create()
    {
        return inertia('produk/Create', [
            'satuans' => Satuan::all(['id', 'nama', 'simbol']),
            'categories' => Category::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProdukRequest $request)
    {
        $validated = $request->validated();

        if ($request->has('overhead_rate')) {
            $validated['overhead_rate_per_unit'] = $request->input('overhead_rate') !== null
                ? (int) round((float) $request->input('overhead_rate') * 100)
                : null;
        }

        $produk = Produk::create($validated);

        if (isset($validated['retail_price']) || isset($validated['wholesale_price'])) {
            $produk->prices()->create([
                'satuan_id' => $produk->satuan_id,
                'purchase_price' => 0,
                'retail_price' => $validated['retail_price'] ?? 0,
                'wholesale_price' => $validated['wholesale_price'] ?? null,
                'is_current' => true,
            ]);
        }

        if ($request->boolean('add_another')) {
            return redirect()->route('produk.create')
                ->with('success', 'Produk berhasil ditambahkan. Silahkan tambah lagi.');
        }

        return redirect()->route('produk.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Produk $produk)
    {
        return inertia('produk/Show', [
            'produk' => $produk->load(['satuan', 'category']),
            'overhead_rate' => $produk->overhead_rate_per_unit ? $produk->overhead_rate_per_unit / 100 : null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Produk $produk)
    {
        $produk->load(['bom.items.produk.satuan', 'bom.items.satuan', 'currentPrice.satuan', 'prices.satuan', 'category']);

        return inertia('produk/Edit', [
            'produk' => $produk,
            'satuans' => Satuan::all(['id', 'nama', 'simbol']),
            'categories' => Category::all(['id', 'name']),
            'overhead_rate' => $produk->overhead_rate_per_unit ? $produk->overhead_rate_per_unit / 100 : null,
        ]);
    }

    public function update(UpdateProdukRequest $request, Produk $produk)
    {
        $validated = $request->validated();

        if ($request->has('overhead_rate')) {
            $validated['overhead_rate_per_unit'] = $request->input('overhead_rate') !== null
                ? (int) round((float) $request->input('overhead_rate') * 100)
                : null;
        }

        $produk->update($validated);

        // Update pricing if provided
        if (isset($validated['retail_price']) || isset($validated['wholesale_price'])) {
            $currentPrice = $produk->currentPrice;

            if ($currentPrice) {
                $currentPrice->update([
                    'retail_price' => $validated['retail_price'] ?? $currentPrice->retail_price,
                    'wholesale_price' => $validated['wholesale_price'] ?? $currentPrice->wholesale_price,
                ]);
            } else {
                // Create a new price record if none exists
                $produk->prices()->create([
                    'satuan_id' => $produk->satuan_id,
                    'purchase_price' => 0,
                    'retail_price' => $validated['retail_price'] ?? 0,
                    'wholesale_price' => $validated['wholesale_price'] ?? null,
                    'is_current' => true,
                ]);
            }
        }

        return redirect()->route('produk.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk)
    {
        $produk->delete();

        return redirect()->route('produk.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:produks,id',
        ]);

        Produk::whereIn('id', $request->ids)->delete();

        return redirect()->route('produk.index')
            ->with('success', count($request->ids).' produk berhasil dihapus.');
    }
}
