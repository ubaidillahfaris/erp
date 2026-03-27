<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(): Response
    {
        $produks = Produk::where('is_active', true)
            ->where('type', 'finished_good')
            ->with(['currentPrice', 'satuan', 'stock'])
            ->get()
            ->map(function ($produk) {
                return [
                    'id' => $produk->id,
                    'nama' => $produk->nama,
                    'sku' => $produk->sku,
                    'barcode' => $produk->barcode,
                    'kategori' => $produk->kategori,
                    'type' => $produk->type,
                    'satuan_id' => $produk->satuan_id,
                    'base_unit' => $produk->satuan->nama,
                    'price' => (float) ($produk->currentPrice?->retail_price ?? 0),
                    'cost' => (float) ($produk->currentPrice?->purchase_price ?? 0),
                    'stock' => (float) ($produk->stock?->balance ?? 0),
                ];
            });

        return Inertia::render('Pos/Index', [
            'produks' => $produks,
        ]);
    }

    public function store(Request $request)
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

        return DB::transaction(function () use ($validated) {
            $totalAmount = collect($validated['items'])->sum(function ($item) {
                return $item['qty'] * $item['price'];
            });

            // Generate simple unique invoice
            $invoiceNumber = 'IV' . now()->format('ymd') . strtoupper(bin2hex(random_bytes(2)));

            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'tanggal' => $validated['tanggal'],
                'total_amount' => $totalAmount,
                'received_amount' => $validated['received_amount'] ?? $totalAmount,
                'change_amount' => $validated['change_amount'] ?? 0,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'notes' => $validated['notes'] ?? null,
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

            return redirect()->route('pos.index')->with('success', 'Transaksi berhasil. No Invoice: ' . $sale->invoice_number);
        });
    }
}
