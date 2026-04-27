<?php

namespace App\Http\Controllers;

use App\Models\StockBatch;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockBatchController extends Controller
{
    public function index(Request $request)
    {
        $query = StockBatch::with(['product', 'warehouse', 'unit']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('product', function ($sub) use ($request) {
                    $sub->where('name', 'like', "%{$request->search}%")
                        ->orWhere('sku', 'like', "%{$request->search}%");
                })->orWhere('batch_number', 'like', "%{$request->search}%");
            });
        }

        $batches = $query->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        return Inertia::render('stock/BatchReport', [
            'batches' => $batches,
            'filters' => $request->only(['warehouse_id', 'status', 'search']),
            'warehouses' => Warehouse::all(),
        ]);
    }

    public function show(StockBatch $batch)
    {
        $batch->load(['product', 'warehouse', 'unit', 'stockMovements.warehouse']);
        
        return Inertia::render('stock/BatchDetail', [
            'batch' => $batch
        ]);
    }
}
