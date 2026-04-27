<?php

namespace App\Http\Controllers;

use App\Actions\RecalculateHpp;
use App\Actions\RecordStockMovement;
use App\Http\Requests\StoreRestockRequest;
use App\Http\Requests\UpdateRestockRequest;
use App\Models\Price;
use App\Models\Product;
use App\Models\Restock;
use App\Models\Unit;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RestockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! app()->runningUnitTests() && ! $request->header('X-Inertia')) {
            return redirect('/purchasing');
        }

        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort') ?: 'date';
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $query = Restock::with(['vendor'])->withCount('items');

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($qv) use ($search) {
                        $qv->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('status') && ! empty($request->status) && $request->status !== 'semua') {
            $query->where('status_pembayaran', $request->status);
        }

        $restocks = $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('restock/Index', [
            'restocks' => $restocks,
            'filters' => $request->only(['search', 'status', 'per_page', 'sort', 'direction']),
        ]);
    }

    public function settle(Restock $restock): RedirectResponse
    {
        $restock->update([
            'status_pembayaran' => 'lunas',
            'total_bayar' => $restock->total_biaya,
        ]);

        return redirect()->back()->with('success', 'Pembayaran restock berhasil dilunasi.');
    }

    public function create(Request $request): Response
    {
        $bahanBakus = Product::with(['unit', 'currentPrice'])->where('type', 'raw_material')->get();

        return Inertia::render('restock/Create', [
            'bahanBakus' => $bahanBakus,
            'units' => Unit::all(['id', 'name', 'symbol']),
            'vendors' => Vendor::all(['id', 'name']),
            'productId' => $request->query('product_id'),
        ]);
    }

    public function store(StoreRestockRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $itemsTotal = collect($request->items)->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $adjustmentsTotal = collect($request->cost_tambahan ?? [])->sum('nominal');
            $totalBiaya = $itemsTotal + $adjustmentsTotal;

            $restock = Restock::create([
                'date' => $request->date,
                'vendor_id' => $request->vendor_id,
                'notes' => $request->notes,
                'status_pembayaran' => $request->status_pembayaran,
                'total_bayar' => $request->total_bayar,
                'biaya_tambahan' => $request->cost_tambahan,
                'total_biaya' => $totalBiaya,
            ]);

            foreach ($request->items as $item) {
                $restock->items()->create($item);
                $this->updateProductPrice($item['product_id'], $item['unit_id'], (float) $item['unit_price']);

                // Recalculate HPP for this product and its dependents
                $product = Product::find($item['product_id']);
                app(RecalculateHpp::class)->handle($product);

                // Record Stock Movement
                app(RecordStockMovement::class)->handle([
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'reference_type' => 'restock',
                    'reference_id' => $restock->id,
                    'notes' => "Restock ref: {$restock->id}",
                ]);
            }
        });

        return redirect()->route('restock.index')->with('success', 'Pencatatan Restock berhasil disimpan.');
    }

    public function edit(Restock $restock): Response
    {
        $restock->load(['items.product.unit', 'vendor']);
        $bahanBakus = Product::with('unit')->where('type', 'raw_material')->get();

        return Inertia::render('restock/Edit', [
            'restock' => $restock,
            'bahanBakus' => $bahanBakus,
            'units' => Unit::all(['id', 'name', 'symbol']),
            'vendors' => Vendor::all(['id', 'name']),
        ]);
    }

    public function update(UpdateRestockRequest $request, Restock $restock): RedirectResponse
    {
        DB::transaction(function () use ($request, $restock) {
            $itemsTotal = collect($request->items)->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $adjustmentsTotal = collect($request->cost_tambahan ?? [])->sum('nominal');
            $totalBiaya = $itemsTotal + $adjustmentsTotal;

            $restock->update([
                'date' => $request->date,
                'vendor_id' => $request->vendor_id,
                'notes' => $request->notes,
                'status_pembayaran' => $request->status_pembayaran,
                'total_bayar' => $request->total_bayar,
                'biaya_tambahan' => $request->cost_tambahan,
                'total_biaya' => $totalBiaya,
            ]);

            $restock->items()->delete();
            foreach ($request->items as $item) {
                $restock->items()->create($item);
                $this->updateProductPrice($item['product_id'], $item['unit_id'], $item['unit_price']);
            }
        });

        return redirect()->route('restock.index')->with('success', 'Pencatatan Restock updated successfully.');
    }

    public function destroy(Restock $restock): RedirectResponse
    {
        $restock->delete();

        return redirect()->route('restock.index')->with('success', 'Data Restock deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:restocks,id',
        ]);

        Restock::whereIn('id', $request->ids)->delete();

        return to_route('restock.index')->with('success', count($request->ids).' data restock deleted successfully.');
    }

    /**
     * Update product price record based on restock data.
     */
    private function updateProductPrice(int $productId, int $unitId, float $purchasePrice): void
    {
        $currentPrice = Price::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('is_current', true)
            ->first();

        // If price is different or doesn't exist, create a new history record
        if (! $currentPrice || (float) $currentPrice->purchase_price !== (float) $purchasePrice) {
            if ($currentPrice) {
                $currentPrice->update(['is_current' => false]);
            }

            Price::create([
                'product_id' => $productId,
                'unit_id' => $unitId,
                'purchase_price' => $purchasePrice,
                'retail_price' => $currentPrice ? $currentPrice->retail_price : 0,
                'wholesale_price' => $currentPrice ? $currentPrice->wholesale_price : null,
                'is_current' => true,
            ]);
        }
    }
}
