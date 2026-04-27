<?php

namespace App\Http\Controllers;

use App\Actions\RecalculateHpp;
use App\Http\Requests\StoreBOMRequest;
use App\Http\Requests\UpdateBOMRequest;
use App\Models\Bom;
use App\Models\Product;
use App\Models\Production;
use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BOMController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort') ?: 'created_at';
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $query = Bom::with('product.unit', 'product.currentPrice', 'yieldUnit');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->wantsJson()) {
            $query->with(['items.product.unit', 'items.unit']);

            return response()->json($query->paginate(10));
        }

        $boms = $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('bom/Index', [
            'boms' => $boms,
            'filters' => $request->only(['search', 'per_page', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $products = Product::whereIn('type', ['finished_good', 'intermediate_good'])
            ->whereDoesntHave('bom')
            ->get();

        $bahanBakus = Product::with(['unit', 'currentPrice'])->whereIn('type', ['raw_material', 'intermediate_good'])->get();

        $units = Unit::all();

        return Inertia::render('bom/Create', [
            'products' => $products,
            'bahanBakus' => $bahanBakus,
            'units' => $units,
            'conversions' => UnitConversion::all(),
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
                $data['sku'] = 'BOM-'.str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }

            $bom = Bom::create($data);

            foreach ($request->items as $item) {
                $bom->items()->create($item);
            }

            // Recalculate HPP for the product
            app(RecalculateHpp::class)->handle($bom->product);
        });

        return redirect()->route('bom.index')->with('success', 'BOM berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bom $bom): Response
    {
        $bom->load(['items.product.unit', 'yieldUnit']);

        $products = Product::whereIn('type', ['finished_good', 'intermediate_good'])
            ->where(function ($query) use ($bom) {
                $query->whereDoesntHave('bom')
                    ->orWhere('id', $bom->product_id);
            })
            ->get();

        $bahanBakus = Product::with(['unit', 'currentPrice'])->whereIn('type', ['raw_material', 'intermediate_good'])->get();

        $units = Unit::all();

        // Get latest production yield if it exists
        $latestProduction = Production::where('bom_id', $bom->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        $latestProductionYield = $latestProduction ? (float) $latestProduction->actual_yield : null;

        return Inertia::render('bom/Edit', [
            'bom' => $bom,
            'products' => $products,
            'bahanBakus' => $bahanBakus,
            'units' => $units,
            'conversions' => UnitConversion::all(),
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
            app(RecalculateHpp::class)->handle($bom->product);
        });

        return redirect()->route('bom.index')->with('success', 'BOM updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bom $bom): RedirectResponse
    {
        $bom->delete();

        return redirect()->route('bom.index')->with('success', 'BOM deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:boms,id',
        ]);

        Bom::whereIn('id', $request->ids)->delete();

        return to_route('bom.index')->with('success', count($request->ids).' BOM deleted successfully.');
    }
}
