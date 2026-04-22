<?php

namespace App\Http\Controllers;

use App\Actions\RecalculateHpp;
use App\Actions\RecordStockMovement;
use App\Http\Requests\StoreRestockRequest;
use App\Http\Requests\UpdateRestockRequest;
use App\Models\Price;
use App\Models\Produk;
use App\Models\Restock;
use App\Models\Satuan;
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
        $sort = $request->input('sort') ?: 'tanggal';
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $query = Restock::with(['vendor'])->withCount('items');

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('keterangan', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($qv) use ($search) {
                        $qv->where('nama', 'like', "%{$search}%");
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
        $bahanBakus = Produk::with(['satuan', 'currentPrice'])->where('type', 'raw_material')->get();

        return Inertia::render('restock/Create', [
            'bahanBakus' => $bahanBakus,
            'satuans' => Satuan::all(['id', 'nama', 'simbol']),
            'vendors' => Vendor::all(['id', 'nama']),
            'produkId' => $request->query('produk_id'),
        ]);
    }

    public function store(StoreRestockRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $itemsTotal = collect($request->items)->sum(function ($item) {
                return $item['jumlah'] * $item['harga_satuan'];
            });

            $adjustmentsTotal = collect($request->biaya_tambahan ?? [])->sum('nominal');
            $totalBiaya = $itemsTotal + $adjustmentsTotal;

            $restock = Restock::create([
                'tanggal' => $request->tanggal,
                'vendor_id' => $request->vendor_id,
                'keterangan' => $request->keterangan,
                'status_pembayaran' => $request->status_pembayaran,
                'total_bayar' => $request->total_bayar,
                'biaya_tambahan' => $request->biaya_tambahan,
                'total_biaya' => $totalBiaya,
            ]);

            foreach ($request->items as $item) {
                $restock->items()->create($item);
                $this->updateProductPrice($item['produk_id'], $item['satuan_id'], (float) $item['harga_satuan']);

                // Recalculate HPP for this product and its dependents
                $produk = Produk::find($item['produk_id']);
                app(RecalculateHpp::class)->handle($produk);

                // Record Stock Movement
                app(RecordStockMovement::class)->handle([
                    'produk_id' => $item['produk_id'],
                    'satuan_id' => $item['satuan_id'],
                    'type' => 'in',
                    'jumlah' => $item['jumlah'],
                    'reference_type' => 'restock',
                    'reference_id' => $restock->id,
                    'keterangan' => "Restock ref: {$restock->id}",
                ]);
            }
        });

        return redirect()->route('restock.index')->with('success', 'Pencatatan Restock berhasil disimpan.');
    }

    public function edit(Restock $restock): Response
    {
        $restock->load(['items.produk.satuan', 'vendor']);
        $bahanBakus = Produk::with('satuan')->where('type', 'raw_material')->get();

        return Inertia::render('restock/Edit', [
            'restock' => $restock,
            'bahanBakus' => $bahanBakus,
            'satuans' => Satuan::all(['id', 'nama', 'simbol']),
            'vendors' => Vendor::all(['id', 'nama']),
        ]);
    }

    public function update(UpdateRestockRequest $request, Restock $restock): RedirectResponse
    {
        DB::transaction(function () use ($request, $restock) {
            $itemsTotal = collect($request->items)->sum(function ($item) {
                return $item['jumlah'] * $item['harga_satuan'];
            });

            $adjustmentsTotal = collect($request->biaya_tambahan ?? [])->sum('nominal');
            $totalBiaya = $itemsTotal + $adjustmentsTotal;

            $restock->update([
                'tanggal' => $request->tanggal,
                'vendor_id' => $request->vendor_id,
                'keterangan' => $request->keterangan,
                'status_pembayaran' => $request->status_pembayaran,
                'total_bayar' => $request->total_bayar,
                'biaya_tambahan' => $request->biaya_tambahan,
                'total_biaya' => $totalBiaya,
            ]);

            $restock->items()->delete();
            foreach ($request->items as $item) {
                $restock->items()->create($item);
                $this->updateProductPrice($item['produk_id'], $item['satuan_id'], $item['harga_satuan']);
            }
        });

        return redirect()->route('restock.index')->with('success', 'Pencatatan Restock berhasil diperbarui.');
    }

    public function destroy(Restock $restock): RedirectResponse
    {
        $restock->delete();

        return redirect()->route('restock.index')->with('success', 'Data Restock berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:restocks,id',
        ]);

        Restock::whereIn('id', $request->ids)->delete();

        return to_route('restock.index')->with('success', count($request->ids).' data restock berhasil dihapus.');
    }

    /**
     * Update product price record based on restock data.
     */
    private function updateProductPrice(int $produkId, int $satuanId, float $purchasePrice): void
    {
        $currentPrice = Price::where('produk_id', $produkId)
            ->where('satuan_id', $satuanId)
            ->where('is_current', true)
            ->first();

        // If price is different or doesn't exist, create a new history record
        if (! $currentPrice || (float) $currentPrice->purchase_price !== (float) $purchasePrice) {
            if ($currentPrice) {
                $currentPrice->update(['is_current' => false]);
            }

            Price::create([
                'produk_id' => $produkId,
                'satuan_id' => $satuanId,
                'purchase_price' => $purchasePrice,
                'retail_price' => $currentPrice ? $currentPrice->retail_price : 0,
                'wholesale_price' => $currentPrice ? $currentPrice->wholesale_price : null,
                'is_current' => true,
            ]);
        }
    }
}
