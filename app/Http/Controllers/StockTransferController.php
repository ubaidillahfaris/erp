<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockTransferController extends Controller
{
    protected $transferService;

    public function __construct(StockTransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'creator'])
            ->when($search, function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                    ->orWhereHas('fromWarehouse', fn ($qw) => $qw->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('toWarehouse', fn ($qw) => $qw->where('name', 'like', "%{$search}%"));
            });

        return Inertia::render('StockTransfers/Index', [
            'transfers' => $query->latest()->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function create()
    {
        return Inertia::render('StockTransfers/Create', [
            'warehouses' => Warehouse::where('is_active', true)->get(),
            'products' => Product::where('is_active', true)->with(['unit', 'stock'])->get(),
            'units' => Unit::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.quantity_requested' => 'required|numeric|min:0.0001',
        ]);

        return DB::transaction(function () use ($validated) {
            $transferNumber = 'TRF-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));

            $transfer = StockTransfer::create([
                'transfer_number' => $transferNumber,
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'notes' => $validated['notes'],
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $transfer->items()->create($item);
            }

            return redirect()->route('stock-transfers.show', $transfer)->with('success', 'Transfer stok berhasil dibuat.');
        });
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['items.product.unit', 'items.unit', 'fromWarehouse', 'toWarehouse', 'creator']);

        return Inertia::render('StockTransfers/Show', [
            'transfer' => $stockTransfer,
        ]);
    }

    public function dispatch(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'draft') {
            return redirect()->back()->with('error', 'Hanya transfer berstatus Draft yang dapat dikirim.');
        }

        try {
            $this->transferService->dispatch($stockTransfer);

            return redirect()->back()->with('success', 'Barang sedang dalam perjalanan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function receive(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'in_transit') {
            return redirect()->back()->with('error', 'Hanya transfer berstatus In Transit yang dapat diterima.');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:stock_transfer_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
        ]);

        $receivedQuantities = collect($validated['items'])->pluck('quantity_received', 'id')->toArray();

        try {
            $this->transferService->receive($stockTransfer, $receivedQuantities);

            return redirect()->back()->with('success', 'Barang berhasil diterima.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, StockTransfer $stockTransfer)
    {
        if (! in_array($stockTransfer->status, ['draft', 'in_transit'])) {
            return redirect()->back()->with('error', 'Transfer yang sudah selesai tidak dapat dibatalkan.');
        }

        $request->validate(['reason' => 'required|string|max:255']);

        try {
            $this->transferService->cancel($stockTransfer, $request->reason);

            return redirect()->back()->with('success', 'Transfer berhasil dibatalkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
