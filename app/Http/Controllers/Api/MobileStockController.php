<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;

/**
 * @group Inventory
 *
 * API untuk cek stok dan manajemen gudang via gadget.
 */
class MobileStockController extends Controller
{
    /**
     * Lookup Barcode
     * 
     * Mencari detail produk berdasarkan Barcode atau ID untuk cek harga & stok kilat.
     * 
     * @queryParam barcode string Barcode produk (required jika ID kosong). Example: 899123456789
     * @queryParam id int ID produk (required jika Barcode kosong). Example: 5
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'barcode' => 'required_without:id',
            'id' => 'required_without:barcode',
        ]);

        $query = Produk::query()->with(['currentPrice', 'satuan', 'stock']);

        if ($request->has('barcode')) {
            $query->where('barcode', $request->barcode);
        } else {
            $query->where('id', $request->id);
        }

        $produk = $query->first();

        if (!$produk) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }

        return new \App\Http\Resources\ProdukResource($produk);
    }

    public function adjustment(Request $request)
    {
        // Panggil logic yang sama dengan StockController
        // Ini asumsikan kita butuh helper atau trait jika logicnya kompleks
        // Untuk sekarang kita biarkan placeholder untuk diskusi teknis
        return response()->json(['message' => 'Feature coming soon'], 501);
    }

    public function opname(Request $request)
    {
        return response()->json(['message' => 'Feature coming soon'], 501);
    }
}
