<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
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
     * Mencari detail product berdasarkan Barcode atau ID untuk cek harga & stok kilat.
     *
     * @queryParam barcode string Barcode product (required jika ID kosong). Example: 899123456789
     * @queryParam id int ID product (required jika Barcode kosong). Example: 5
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'barcode' => 'required_without:id',
            'id' => 'required_without:barcode',
        ]);

        $query = Product::query()->with(['currentPrice', 'unit', 'stock']);

        if ($request->has('barcode')) {
            $query->where('barcode', $request->barcode);
        } else {
            $query->where('id', $request->id);
        }

        $product = $query->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product tidak ditemukan',
            ], 404);
        }

        return new ProductResource($product);
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
