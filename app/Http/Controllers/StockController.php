<?php

namespace App\Http\Controllers;

use App\Actions\RecordStockMovement;
use App\Jobs\GenerateStockMutationPdfJob;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::query()
            ->whereIn('type', ['raw_material', 'intermediate_good'])
            ->with(['stock', 'unit'])
            ->withCount('stockMovements');
        $perPage = $request->input('per_page', 10);

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('sku', 'like', "%{$request->search}%");
        }

        if ($request->has('type') && ! empty($request->type)) {
            $query->where('type', $request->type);
        }

        $products = $query->paginate($perPage)->withQueryString();
        $units = Unit::all();
        $conversions = UnitConversion::all();

        return Inertia::render('stock/Index', [
            'products' => $products,
            'units' => $units,
            'conversions' => $conversions,
            'filters' => $request->only(['search', 'type', 'per_page']),
        ]);
    }

    public function show(Product $product, Request $request): Response
    {
        $product->load(['stock', 'unit']);

        $perPage = $request->input('per_page', 10);

        $movements = StockMovement::where('product_id', $product->id)
            ->with('unit')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('stock/Show', [
            'product' => $product,
            'movements' => $movements,
            'filters' => $request->only(['per_page']),
        ]);
    }

    public function adjustment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_id' => 'required|exists:units,id',
            'type' => 'sometimes|in:in,out',
            'quantity' => 'sometimes|numeric',
            'physical_qty' => 'sometimes|numeric',
            'notes' => 'required|string|max:255',
        ]);

        if ($request->has('physical_qty')) {
            $product = Product::with('stock')->findOrFail($request->product_id);
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
        ]);

        GenerateStockMutationPdfJob::dispatch($validated);

        return redirect()->back()->with('success', 'Laporan mutasi sedang dibuat di background. Silakan cek folder storage/app/private/reports beberapa saat lagi.');
    }
}
