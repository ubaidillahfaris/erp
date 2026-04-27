<?php

namespace App\Http\Controllers;

use App\Actions\CompleteProduction;
use App\Exceptions\MissingOverheadRateException;
use App\Http\Requests\StoreProductionRequest;
use App\Http\Requests\UpdateProductionRequest;
use App\Models\Product;
use App\Models\Production;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductionController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort') ?: 'date';
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $query = Production::with(['product', 'bom', 'items.product.currentPrice']);

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('sku', 'like', "%{$request->search}%")
                ->orWhereHas('product', function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%");
                });
        }

        $paginator = $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        // Add estimated cost for in-progress productions
        $paginator->getCollection()->transform(function ($production) {
            if ($production->status === 'in_progress' && is_null($production->total_cost)) {
                $estimatedCost = 0.0;
                foreach ($production->items as $item) {
                    $basePrice = (float) $item->unit_price;
                    if ($basePrice <= 0) {
                        $basePrice = (float) ($item->product->currentPrice->purchase_price ?? 0);
                    }
                    $ratio = 1.0;
                    if ($item->product && $item->product->unit_id !== $item->unit_id) {
                        $ratio = app(UnitService::class)->getConversionRatio($item->product->unit_id, $item->unit_id);
                    }
                    $estimatedCost += ($basePrice / ($ratio ?: 1)) * (float) $item->planned_qty;
                }
                $production->total_cost = $estimatedCost;
                $production->is_estimated = true;
            }

            return $production;
        });

        return inertia('production/Index', [
            'productions' => $paginator,
            'filters' => $request->only(['search', 'per_page', 'sort', 'direction']),
        ]);
    }

    public function create(Request $request): Response
    {
        $units = Unit::all();
        $conversions = UnitConversion::all();

        $reproduceFrom = null;
        if ($request->has('reproduce_from')) {
            $reproduceFrom = Production::with(['items.product.unit', 'product.unit', 'bom'])
                ->find($request->reproduce_from);
        }

        return Inertia::render('production/Create', [
            'boms' => [],
            'units' => $units,
            'conversions' => $conversions,
            'reproduceFrom' => $reproduceFrom,
        ]);
    }

    public function store(StoreProductionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();

            if (empty($data['sku'])) {
                $latest = Production::latest('id')->first();
                $nextId = $latest ? $latest->id + 1 : 1;
                $data['sku'] = 'PRD-'.date('ym').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }

            $data['status'] = 'in_progress';

            $production = Production::create($data);

            foreach ($request->items as $item) {
                // Capture current HPP for estimation
                $ingredient = Product::with('currentPrice')->find($item['product_id']);
                $currentHpp = $ingredient->currentPrice ? $ingredient->currentPrice->purchase_price : 0;

                $production->items()->create([
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'planned_qty' => $item['planned_qty'],
                    'actual_qty' => 0,
                    'unit_price' => $currentHpp,
                ]);
            }
        });

        return redirect()->route('production.index')->with('success', 'Productsi berhasil dimulai.');
    }

    public function show(Production $production): Response
    {
        $production->load(['product.unit', 'bom', 'items.product.unit', 'items.unit', 'items.product.currentPrice']);

        $estimatedTotal = 0.0;

        // Calculate subtotal for each item including conversion
        $production->items->each(function ($item) use ($production, &$estimatedTotal) {
            // Use current price if unit_price is not set (for legacy records)
            $basePrice = (float) $item->unit_price;
            if ($basePrice <= 0) {
                $basePrice = (float) ($item->product->currentPrice->purchase_price ?? 0);
            }
            $ratio = 1.0;

            if ($item->product && $item->product->unit_id !== $item->unit_id) {
                $ratio = app(UnitService::class)->getConversionRatio($item->product->unit_id, $item->unit_id);
            }

            // If in progress, use planned_qty for the "cost" attribute used in UI
            $qty = ($production->status === 'in_progress' && (float) $item->actual_qty == 0) ? $item->planned_qty : $item->actual_qty;
            $item->cost = ($basePrice / ($ratio ?: 1)) * (float) $qty;

            $estimatedTotal += $item->cost;
        });

        if ($production->status === 'in_progress' && is_null($production->total_cost)) {
            $production->total_cost = (float) $estimatedTotal;
            $production->is_estimated = true;
        }

        return Inertia::render('production/Show', [
            'production' => $production,
        ]);
    }

    public function edit(Production $production)
    {
        if ($production->status === 'completed') {
            return redirect()->route('production.show', $production)
                ->with('error', 'Produksi yang sudah selesai tidak dapat diubah.');
        }

        $production->load(['product.unit', 'bom', 'items.product.unit', 'items.unit', 'items.product.currentPrice']);

        $units = Unit::all();
        $conversions = UnitConversion::all();

        return Inertia::render('production/Edit', [
            'production' => $production,
            'units' => $units,
            'conversions' => $conversions,
        ]);
    }

    public function update(UpdateProductionRequest $request, Production $production): RedirectResponse
    {
        if ($production->status === 'completed') {
            abort(403, 'Productsi sudah selesai dan tidak dapat diubah lagi.');
        }

        try {
            DB::transaction(function () use ($request, $production) {
                $validated = $request->validated();

                $totalCost = 0;

                foreach ($validated['items'] as $itemData) {
                    // Update item actual_qty
                    $item = $production->items()->find($itemData['id']);

                    // Get the current HPP of the ingredient
                    $ingredientProduct = Product::with('currentPrice')->find($itemData['product_id']);
                    $basePricePerUnit = $ingredientProduct->currentPrice ? $ingredientProduct->currentPrice->purchase_price : 0;

                    // Calculate scale factor if units differ
                    $ingredientBaseUnitId = $ingredientProduct->unit_id;
                    $usedUnitId = $itemData['unit_id'];

                    $conversionRatio = 1;
                    if ($ingredientBaseUnitId !== $usedUnitId) {
                        $conversionRatio = app(UnitService::class)->getConversionRatio($ingredientBaseUnitId, $usedUnitId);
                    }

                    // Cost for this ingredient = (price_per_base_unit / conversion_multiplier) * (qty_used)
                    $itemCost = ($basePricePerUnit / ($conversionRatio ?: 1)) * $itemData['actual_qty'];
                    $totalCost += $itemCost;

                    $item->update([
                        'actual_qty' => $itemData['actual_qty'],
                        'unit_price' => $basePricePerUnit,
                    ]);
                }

                // Update header
                $production->update([
                    'actual_yield' => $validated['actual_yield'],
                    'status' => 'completed',
                    'total_cost' => $totalCost,
                ]);

                // Note: Dedicated Action to recalculate HPP & update Stock goes here
                // We can optionally use Observers or an Action class similar to RecalculateHpp
                app(CompleteProduction::class)->handle($production);
            });
        } catch (MissingOverheadRateException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('production.index')->with('success', 'Productsi berhasil diselesaikan.');
    }

    public function destroy(Production $production): RedirectResponse
    {
        // Add safeguard to prevent deleting completed productions unless handling reversals
        if ($production->status === 'completed') {
            abort(403, 'Productsi yang sudah selesai tidak dapat dihapus.');
        }

        $production->delete();

        return redirect()->route('production.index')->with('success', 'Productsi deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:productions,id',
        ]);

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($request->ids as $id) {
            $production = Production::find($id);
            if ($production && $production->status !== 'completed') {
                $production->delete();
                $deletedCount++;
            } else {
                $skippedCount++;
            }
        }

        $message = "{$deletedCount} data productsi deleted successfully.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} data dilewati karena sudah selesai.";
        }

        return to_route('production.index')->with($deletedCount > 0 ? 'success' : 'error', $message);
    }
}
