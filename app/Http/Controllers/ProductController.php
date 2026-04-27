<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
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
        $query = Product::query()
            ->with(['unit', 'currentPrice', 'category'])
            ->addSelect(['stok' => Stock::select('balance')
                ->whereColumn('product_id', 'products.id')
                ->limit(1),
            ])
            ->when($jenis && $jenis !== 'all', function ($query) use ($jenis) {
                if (is_array($jenis)) {
                    $query->whereIn('type', $jenis);
                } else {
                    $query->where('type', $jenis);
                }
            });

        if ($request->filled('search')) {
            $products = Product::search($search)
                ->query(fn ($q) => $q->mergeConstraintsFrom($query)->orderBy($sort, $direction))
                ->paginate($perPage);
        } else {
            $products = $query->orderBy($sort, $direction)
                ->paginate($perPage);
        }

        return Inertia::render('product/Index', [
            'products' => $products->withQueryString(),
            'filters' => $request->only(['search', 'active_filters', 'per_page', 'sort', 'direction']),
        ]);
    }

    public function create()
    {
        return inertia('product/Create', [
            'units' => Unit::all(['id', 'name', 'symbol']),
            'categories' => Category::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        if ($request->has('overhead_rate')) {
            $validated['overhead_rate_per_unit'] = $request->input('overhead_rate') !== null
                ? (int) round((float) $request->input('overhead_rate') * 100)
                : null;
        }

        $product = Product::create($validated);

        if (isset($validated['retail_price']) || isset($validated['wholesale_price'])) {
            $product->prices()->create([
                'unit_id' => $product->unit_id,
                'purchase_price' => 0,
                'retail_price' => $validated['retail_price'] ?? 0,
                'wholesale_price' => $validated['wholesale_price'] ?? null,
                'is_current' => true,
            ]);
        }

        if ($request->boolean('add_another')) {
            return redirect()->route('product.create')
                ->with('success', 'Product added successfully. Silahkan tambah lagi.');
        }

        return redirect()->route('product.index')
            ->with('success', 'Product added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return inertia('product/Show', [
            'product' => $product->load(['unit', 'category']),
            'overhead_rate' => $product->overhead_rate_per_unit ? $product->overhead_rate_per_unit / 100 : null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $product->load(['bom.items.product.unit', 'bom.items.unit', 'currentPrice.unit', 'prices.unit', 'category']);

        return inertia('product/Edit', [
            'product' => $product,
            'units' => Unit::all(['id', 'name', 'symbol']),
            'categories' => Category::all(['id', 'name']),
            'overhead_rate' => $product->overhead_rate_per_unit ? $product->overhead_rate_per_unit / 100 : null,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        if ($request->has('overhead_rate')) {
            $validated['overhead_rate_per_unit'] = $request->input('overhead_rate') !== null
                ? (int) round((float) $request->input('overhead_rate') * 100)
                : null;
        }

        $product->update($validated);

        // Update pricing if provided
        if (isset($validated['retail_price']) || isset($validated['wholesale_price'])) {
            $currentPrice = $product->currentPrice;

            if ($currentPrice) {
                $currentPrice->update([
                    'retail_price' => $validated['retail_price'] ?? $currentPrice->retail_price,
                    'wholesale_price' => $validated['wholesale_price'] ?? $currentPrice->wholesale_price,
                ]);
            } else {
                // Create a new price record if none exists
                $product->prices()->create([
                    'unit_id' => $product->unit_id,
                    'purchase_price' => 0,
                    'retail_price' => $validated['retail_price'] ?? 0,
                    'wholesale_price' => $validated['wholesale_price'] ?? null,
                    'is_current' => true,
                ]);
            }
        }

        return redirect()->route('product.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('product.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
        ]);

        Product::whereIn('id', $request->ids)->delete();

        return redirect()->route('product.index')
            ->with('success', count($request->ids).' product deleted successfully.');
    }
}
