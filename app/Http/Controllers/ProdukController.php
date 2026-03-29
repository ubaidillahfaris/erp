<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProdukRequest;
use App\Http\Requests\UpdateProdukRequest;
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
        $jenis = $request->input('jenis');

        if ($request->filled('search')) {
            $produks = Produk::search($search)
                ->when($request->filled('jenis') && $jenis !== 'all', function ($query) use ($jenis) {
                    $query->where('type', $jenis);
                })
                ->query(fn ($q) => $q->with(['satuan', 'currentPrice'])->latest())
                ->paginate($perPage);
        } else {
            $produks = Produk::query()
                ->when($request->filled('jenis') && $jenis !== 'all', function ($query) use ($jenis) {
                    $query->where('type', $jenis);
                })
                ->with(['satuan', 'currentPrice'])
                ->latest()
                ->paginate($perPage);
        }

        return Inertia::render('produk/Index', [
            'produks' => $produks->withQueryString(),
            'filters' => $request->only(['search', 'jenis', 'per_page']),
        ]);
    }

    public function create()
    {
        return inertia('produk/Create', [
            'satuans' => Satuan::all(['id', 'nama', 'simbol']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProdukRequest $request)
    {
        $validated = $request->validated();
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
            'produk' => $produk->load('satuan'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Produk $produk)
    {
        $produk->load(['bom.items.produk.satuan', 'bom.items.satuan', 'currentPrice.satuan', 'prices.satuan']);

        return inertia('produk/Edit', [
            'produk' => $produk,
            'satuans' => Satuan::all(['id', 'nama', 'simbol']),
        ]);
    }

    public function update(UpdateProdukRequest $request, Produk $produk)
    {
        $validated = $request->validated();
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
}
