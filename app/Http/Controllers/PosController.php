<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerPrice;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleCustomer;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('superadmin');

        $warehouseId = $request->input('warehouse_id');
        $defaultWarehouseId = Warehouse::where('is_default', true)->value('id');

        // Logic: If NOT SuperAdmin and has a linked warehouse, ALWAYS use the linked one.
        if (! $isSuperAdmin && $user->warehouse_id) {
            $targetWarehouseId = $user->warehouse_id;
        } else {
            $targetWarehouseId = $warehouseId ?: $defaultWarehouseId;
        }

        $products = Product::where('is_active', true)
            ->where('type', 'finished_good')
            ->with(['currentPrice', 'unit', 'stock' => function ($q) use ($targetWarehouseId) {
                $q->where('warehouse_id', $targetWarehouseId)
                    ->where('is_sellable', true);
            }, 'category'])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'category_id' => $product->category_id,
                    'kategori' => $product->category?->name,
                    'type' => $product->type,
                    'unit_id' => $product->unit_id,
                    'base_unit' => $product->unit->name,
                    'price' => (float) ($product->currentPrice?->retail_price ?? 0),
                    'cost' => (float) ($product->currentPrice?->purchase_price ?? 0),
                    'stock' => (float) ($product->stock->first()?->balance ?? 0),
                    'track_stock' => (bool) $product->track_stock,
                ];
            });

        $categories = Category::whereHas('products', function ($query) {
            $query->where('type', 'finished_good')
                ->where('is_active', true);
        })->orderBy('name')->get(['id', 'name']);

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

        $warehouses = Warehouse::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Pos/Index', [
            'products' => $products,
            'customers' => $customers,
            'categories' => $categories,
            'warehouses' => $warehouses,
            'currentWarehouseId' => (int) $targetWarehouseId,
        ]);
    }

    /**
     * Get price for a specific product, unit, and customer.
     */
    public function getPrice(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_id' => 'required|exists:units,id',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $productId = $request->product_id;
        $unitId = $request->unit_id;
        $customerId = $request->customer_id;

        // Ensure it's a finished good
        $product = Product::where('id', $productId)->where('type', 'finished_good')->firstOrFail();
        $currentPrice = $product->currentPrice;

        if (! $currentPrice) {
            return response()->json([
                'price' => 0,
                'original_price' => 0,
                'discount_rate' => 0,
                'price_type' => 'retail',
            ]);
        }

        $basePrice = (float) $currentPrice->retail_price;
        $priceType = 'retail';

        // 1. Check for Custom Price
        if ($customerId) {
            $customPrice = CustomerPrice::where('customer_id', $customerId)
                ->where('product_id', $productId)
                ->where('unit_id', $unitId)
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
                    ->where('category_id', $product->category_id)
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
            'price_type' => $priceType,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'payment_method' => ['required', 'string'],
            'customer_id' => ['nullable', 'exists:customers,id', 'required_if:payment_method,credit'],
            'received_amount' => ['nullable', 'numeric', 'min:0'],
            'change_amount' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
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
                $invoiceNumber = 'IV'.now()->format('ymd').strtoupper(bin2hex(random_bytes(2)));

                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'warehouse_id' => $validated['warehouse_id'],
                    'date' => $validated['date'],
                    'total_amount' => $totalAmount,
                    'received_amount' => $validated['received_amount'] ?? $totalAmount,
                    'change_amount' => $validated['change_amount'] ?? 0,
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'pending', // Set to pending initially
                ]);

                // Save items
                foreach ($validated['items'] as $item) {
                    $sale->items()->create([
                        'product_id' => $item['product_id'],
                        'unit_id' => $item['unit_id'],
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'cost' => $item['cost'],
                        'subtotal' => $item['qty'] * $item['price'],
                    ]);
                }

                // Finalize sale to trigger observers (Calculate COGS + Journaling)
                $sale->update(['status' => 'completed']);

                // Record Customer Sale
                if (! empty($validated['customer_id'])) {
                    SaleCustomer::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $validated['customer_id'],
                    ]);
                }

                return redirect()->route('pos.index', ['warehouse_id' => $validated['warehouse_id']])->with('success', 'Transaksi berhasil. No Invoice: '.$sale->invoice_number);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }
    }
}
