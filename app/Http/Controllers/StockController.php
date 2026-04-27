<?php

namespace App\Http\Controllers;

use App\Actions\RecordStockMovement;
use App\Jobs\GenerateStockMutationPdfJob;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\Warehouse;
use App\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function index(Request $request): Response
    {
        $warehouseId = $request->input('warehouse_id');

        $query = Product::query()
            ->with(['unit'])
            ->withCount('stockMovements');

        if ($warehouseId && $warehouseId !== 'all') {
            $query->with(['stock' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }]);
            $currentWarehouseId = (int) $warehouseId;
        } else {
            // Consolidated view
            $query->withSum('stocks as total_balance', 'balance');
            $currentWarehouseId = 'all';
        }

        $perPage = $request->input('per_page', 10);
        $minStock = $request->input('min_stock');
        $maxStock = $request->input('max_stock');

        if ($request->has('search') && ! empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('type') && ! empty($request->type) && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Stock Level Filtering
        if ($warehouseId && $warehouseId !== 'all') {
            if ($minStock !== null || $maxStock !== null) {
                $query->whereHas('stocks', function ($q) use ($warehouseId, $minStock, $maxStock) {
                    $q->where('warehouse_id', $warehouseId);
                    if ($minStock !== null) {
                        $q->where('balance', '>=', $minStock);
                    }
                    if ($maxStock !== null) {
                        $q->where('balance', '<=', $maxStock);
                    }
                });
            }
        } else {
            if ($minStock !== null || $maxStock !== null) {
                // For consolidated view, we use having if we used withSum,
                // but withSum adds a subquery column, so we might need to use a subquery in where
                $query->where(function ($q) use ($minStock, $maxStock) {
                    $subquery = \DB::table('stocks')
                        ->selectRaw('SUM(balance)')
                        ->whereColumn('product_id', 'products.id');

                    if ($minStock !== null) {
                        $q->whereRaw("({$subquery->toSql()}) >= ?", [$minStock]);
                    }
                    if ($maxStock !== null) {
                        $q->whereRaw("({$subquery->toSql()}) <= ?", [$maxStock]);
                    }
                });
            }
        }

        $products = $query->paginate($perPage)->withQueryString();

        // Transform products to have a consistent 'balance' attribute
        $products->getCollection()->transform(function ($product) use ($warehouseId) {
            if ($warehouseId && $warehouseId !== 'all') {
                $product->display_balance = $product->stock->balance ?? 0;
            } else {
                $product->display_balance = $product->total_balance ?? 0;
            }

            return $product;
        });

        $units = Unit::all();
        $conversions = UnitConversion::all();
        $warehouses = Warehouse::all();

        return Inertia::render('stock/Index', [
            'products' => $products,
            'units' => $units,
            'conversions' => $conversions,
            'warehouses' => $warehouses,
            'currentWarehouseId' => $currentWarehouseId,
            'filters' => $request->only(['search', 'type', 'per_page', 'warehouse_id', 'min_stock', 'max_stock']),
        ]);
    }

    public function show(Product $product, Request $request): Response
    {
        $warehouseId = $request->input('warehouse_id');
        $defaultWarehouseId = Warehouse::where('is_default', true)->value('id');
        $targetWarehouseId = ($warehouseId && $warehouseId !== 'all') ? $warehouseId : $defaultWarehouseId;

        $product->load(['stock' => function ($q) use ($targetWarehouseId) {
            $q->where('warehouse_id', $targetWarehouseId);
        }, 'unit'])->loadSum('stocks as total_balance', 'balance');

        $perPage = $request->input('per_page', 10);

        $movementsQuery = StockMovement::where('product_id', $product->id);

        if ($warehouseId && $warehouseId !== 'all') {
            $movementsQuery->where('warehouse_id', $warehouseId);
        }

        $movements = $movementsQuery->with('unit', 'warehouse')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('stock/Show', [
            'product' => $product,
            'movements' => $movements,
            'warehouses' => Warehouse::all(),
            'currentWarehouseId' => $targetWarehouseId,
            'filters' => $request->only(['per_page', 'warehouse_id']),
        ]);
    }

    public function adjustment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'unit_id' => 'required|exists:units,id',
            'type' => 'sometimes|in:in,out',
            'quantity' => 'sometimes|numeric',
            'physical_qty' => 'sometimes|numeric',
            'notes' => 'required|string|max:255',
        ]);

        if ($request->has('physical_qty')) {
            $product = Product::with(['stock' => function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            }])->findOrFail($request->product_id);

            $currentBalance = (float) ($product->stock->balance ?? 0);

            // Convert physical_qty to base unit
            $ratio = app(UnitService::class)->getConversionRatio($product->unit_id, $request->unit_id);
            $physicalQtyBase = (float) $request->physical_qty / ($ratio ?: 1);

            $diff = $physicalQtyBase - $currentBalance;

            if (abs($diff) < 0.000001) {
                return redirect()->back()->with('success', 'Stok sudah sesuai, tidak ada penyesuaian.');
            }

            $validated['type'] = $diff > 0 ? 'in' : 'out';
            $validated['quantity'] = abs($diff) * ($ratio ?: 1);
        }

        app(RecordStockMovement::class)->handle(array_merge($validated, [
            'reference_type' => 'adjustment',
            'reference_id' => auth()->id(),
        ]));

        return redirect()->back()->with('success', 'Penyesuaian stok berhasil disimpan.');
    }

    public function exportMutationPdf(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'product_id' => 'nullable|exists:products,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        GenerateStockMutationPdfJob::dispatch($validated);

        return redirect()->back()->with('success', 'Laporan mutasi sedang dibuat di background. Silakan cek folder storage/app/private/reports beberapa saat lagi.');
    }
}
