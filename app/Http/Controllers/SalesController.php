<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\StornoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalesController extends Controller
{
    public function __construct(
        protected StornoService $stornoService
    ) {}

    /**
     * Display a listing of sales.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort', 'tanggal');
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $query = Sale::query()
            ->with([
                'saleCustomer.customer', 
                'items.produk', 
                'items.satuan',
                'payable' => function($q) {
                    $q->select('id', 'reference_id', 'reference_type', 'status', 'total_amount', 'remaining_amount');
                }
            ])
            ->latest('tanggal');

        // Filters
        $query->when($request->search, function ($query, $search) {
            $query->where('invoice_number', 'like', "%{$search}%");
        });

        $query->when($request->date_start, function ($query, $date) {
            $query->whereDate('tanggal', '>=', $date);
        });

        $query->when($request->date_end, function ($query, $date) {
            $query->whereDate('tanggal', '<=', $date);
        });

        $query->when($request->payment_method, function ($query, $method) {
            $query->where('payment_method', $method);
        });

        $query->when($request->status, function ($query, $status) {
            $query->where('status', $status);
        });

        $sales = $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'filters' => $request->only(['search', 'date_start', 'date_end', 'payment_method', 'status', 'per_page', 'sort', 'direction']),
        ]);
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale)
    {
        $sale->load([
            'saleCustomer.customer',
            'items.produk',
            'items.satuan', 
            'payable.payments.createdBy'
        ]);

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
            'payable' => $sale->payable,
        ]);
    }

    /**
     * Void the specified sale.
     */
    public function void(Request $request, Sale $sale)
    {
        // Check permission
        if (!$request->user()->can('void sales')) {
            abort(403, 'Anda tidak memiliki akses untuk membatalkan transaksi ini.');
        }

        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        try {
            // Re-load items to ensure they are available for storno
            $sale->load('items');
            
            $this->stornoService->perform($sale, $request->reason);

            return back()->with('success', 'Transaksi penjualan #' . $sale->invoice_number . ' telah dibatalkan (voided).');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }
}
