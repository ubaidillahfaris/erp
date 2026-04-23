<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProdukResource;
use App\Models\Produk;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group POS & Scanner
 *
 * API untuk transaksi penjualan dan pencarian produk kasir mobile.
 */
class MobilePosController extends Controller
{
    /**
     * List Produk POS
     *
     * Mengambil daftar produk yang aktif untuk dijual (finished_good).
     *
     * @queryParam search string Filter berdasarkan Nama, SKU, atau Barcode. Example: Kopi
     */
    public function products(Request $request)
    {
        $query = Produk::where('is_active', true)
            ->where('type', 'finished_good')
            ->with(['currentPrice', 'satuan', 'stock']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $produks = $query->paginate($request->integer('per_page', 10));

        return ProdukResource::collection($produks);
    }

    /**
     * Checkout Penjualan
     *
     * Menyimpan transaksi penjualan baru dari aplikasi mobile.
     *
     * @bodyParam tanggal date required Tanggal transaksi (YYYY-MM-DD). Example: 2024-03-18
     * @bodyParam payment_method string required Metode pembayaran (cash, qris, transfer, credit). Example: cash
     * @bodyParam received_amount number Jumlah uang diterima. Example: 50000
     * @bodyParam change_amount number Kembalian. Example: 5000
     * @bodyParam items object[] required List barang yang dibeli.
     * @bodyParam items[].produk_id int required ID Produk. Example: 1
     * @bodyParam items[].satuan_id int required ID Satuan. Example: 1
     * @bodyParam items[].qty number required Jumlah barang. Example: 2
     * @bodyParam items[].price number required Harga jual per satuan. Example: 15000
     * @bodyParam items[].cost number required Harga modal per satuan. Example: 10000
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'payment_method' => ['required', 'string'],
            'received_amount' => ['nullable', 'numeric', 'min:0'],
            'change_amount' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['required', 'exists:produks,id'],
            'items.*.satuan_id' => ['required', 'exists:satuans,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.001'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.cost' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $totalAmount = collect($validated['items'])->sum(function ($item) {
                    return $item['qty'] * $item['price'];
                });

                $invoiceNumber = 'IVM'.now()->format('ymd').strtoupper(bin2hex(random_bytes(2)));

                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'tanggal' => $validated['tanggal'],
                    'total_amount' => $totalAmount,
                    'received_amount' => $validated['received_amount'] ?? $totalAmount,
                    'change_amount' => $validated['change_amount'] ?? 0,
                    'payment_method' => $validated['payment_method'],
                    'notes' => $validated['notes'] ?? 'Mobile Transaction',
                ]);

                foreach ($validated['items'] as $item) {
                    $sale->items()->create([
                        'produk_id' => $item['produk_id'],
                        'satuan_id' => $item['satuan_id'],
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'cost' => $item['cost'],
                        'subtotal' => $item['qty'] * $item['price'],
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi berhasil',
                    'data' => [
                        'invoice_number' => $sale->invoice_number,
                        'total_amount' => $sale->total_amount,
                    ],
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal simpan transaksi: '.$e->getMessage(),
            ], 500);
        }
    }
}
