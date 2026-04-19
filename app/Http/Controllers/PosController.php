<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPrice;
use App\Models\Produk;
use App\Models\Sale;
use App\Models\SaleCustomer;
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

        $customers = Customer::whereHas('status', function ($query) {
            $query->where('name', 'Active');
        })
        ->with('type')
        ->get()
        ->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'type' => $customer->type?->name,
            ];
        });

        return Inertia::render('Pos/Index', [
            'produks' => $produks,
            'customers' => $customers,
        ]);
    }

    /**
     * Get price for a specific product, unit, and customer.
     */
    public function getPrice(Request $request)
    {
        $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'satuan_id' => 'required|exists:satuans,id',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $produkId = $request->produk_id;
        $satuanId = $request->satuan_id;
        $customerId = $request->customer_id;

        // Ensure it's a finished good
        $produk = Produk::where('id', $produkId)->where('type', 'finished_good')->firstOrFail();
        $currentPrice = $produk->currentPrice;

        if (!$currentPrice) {
            return response()->json([
                'price' => 0, 
                'original_price' => 0,
                'discount_rate' => 0,
                'price_type' => 'retail'
            ]);
        }

        $basePrice = (float) $currentPrice->retail_price;
        $priceType = 'retail';

        // 1. Check for Custom Price
        if ($customerId) {
            $customPrice = CustomerPrice::where('customer_id', $customerId)
                ->where('produk_id', $produkId)
                ->where('satuan_id', $satuanId)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('valid_until')
                        ->orWhere('valid_until', '>=', now()->toDateString());
                })
                ->first();

            if ($customPrice) {
                $basePrice = (float) $customPrice->custom_price;
                $priceType = 'custom';
            } else {
                // 2. Fallback to Customer Type logic
                $customer = Customer::with('type')->find($customerId);
                $typeName = $customer->type?->name;

                if (in_array($typeName, ['Wholesale', 'Dropship'])) {
                    $basePrice = (float) $currentPrice->wholesale_price;
                    $priceType = 'wholesale';
                }
            }
        }

        // 3. Discount Calculation
        $discountRate = 0;
        if ($customerId) {
            $customer = Customer::with(['creditSetting', 'categoryDiscounts'])->find($customerId);
            
            if ($customer) {
                // a. Check Category Discount
                $categoryDiscount = $customer->categoryDiscounts()
                    ->where('kategori', $produk->kategori)
                    ->where('is_active', true)
                    ->first();

                if ($categoryDiscount) {
                    $discountRate = (float) $categoryDiscount->discount_rate;
                } 
                // b. Fallback to Global Discount
                elseif ($customer->creditSetting?->global_discount > 0) {
                    $discountRate = (float) $customer->creditSetting->global_discount;
                }
            }
        }

        $finalPrice = $basePrice * (1 - $discountRate / 100);

        return response()->json([
            'price' => $finalPrice,
            'original_price' => $basePrice,
            'discount_rate' => $discountRate,
            'price_type' => $priceType
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'payment_method' => ['required', 'string'],
            'customer_id' => ['nullable', 'exists:customers,id', 'required_if:payment_method,credit'],
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

                // Save items
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

                // Record Customer Sale
                if (!empty($validated['customer_id'])) {
                    SaleCustomer::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $validated['customer_id'],
                    ]);
                }

                return redirect()->route('pos.index')->with('success', 'Transaksi berhasil. No Invoice: ' . $sale->invoice_number);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }
    }
}
