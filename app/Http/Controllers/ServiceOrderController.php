<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceOrderController extends Controller
{
    /**
     * Display the Service Order POS page (Laundry).
     */
    public function create(Request $request): Response
    {
        $products = Product::where('is_active', true)
            ->where('type', 'service')
            ->with(['currentPrice', 'unit', 'category'])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category_id' => $product->category_id,
                    'unit_id' => $product->unit_id,
                    'unit_symbol' => $product->unit->symbol,
                    'price' => (float) ($product->currentPrice?->retail_price ?? 0),
                ];
            });

        $customers = Customer::whereHas('status', function ($query) {
            $query->where('name', 'Active');
        })->get(['id', 'name']);

        $warehouses = Warehouse::where('is_active', true)->get(['id', 'name']);
        $defaultWarehouseId = Warehouse::where('is_default', true)->value('id');

        return Inertia::render('ServiceOrders/Create', [
            'products' => $products,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'defaultWarehouseId' => $defaultWarehouseId,
        ]);
    }

    /**
     * Store a newly created Service Order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_type' => 'required|string',
            'estimated_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $prefix = strtoupper(substr($validated['order_type'], 0, 3));
            $orderNumber = $prefix.'-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(2)));

            $order = ServiceOrder::create([
                'customer_id' => $validated['customer_id'],
                'order_type' => $validated['order_type'],
                'order_number' => $orderNumber,
                'status' => 'pending',
                'estimated_at' => $validated['estimated_at'],
                'notes' => $validated['notes'],
                'metadata' => $validated['metadata'],
            ]);

            foreach ($validated['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['qty'] * $item['price'],
                ]);
            }

            return redirect()->route('service-orders.board')->with('success', 'Order berhasil dibuat: '.$orderNumber);
        });
    }

    /**
     * Display the Service Order Board.
     */
    public function board(): Response
    {
        $orders = ServiceOrder::with(['customer', 'items.product'])
            ->where('status', '!=', 'picked_up')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('ServiceOrders/Board', [
            'orders' => $orders,
        ]);
    }

    /**
     * Update the status of a Service Order.
     */
    public function updateStatus(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,ready,picked_up,cancelled',
        ]);

        $status = $request->status;
        $updateData = ['status' => $status];

        if ($status === 'ready') {
            $updateData['ready_at'] = now();
        } elseif ($status === 'picked_up') {
            $updateData['picked_up_at'] = now();
        }

        $serviceOrder->update($updateData);

        return back()->with('success', 'Status order berhasil diperbarui.');
    }

    /**
     * Process payment for a Service Order.
     */
    public function pay(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'received_amount' => 'required|numeric|min:0',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        if ($serviceOrder->sale_id) {
            return back()->withErrors(['pay' => 'Order ini sudah dibayar.']);
        }

        return DB::transaction(function () use ($request, $serviceOrder) {
            $totalAmount = $serviceOrder->items->sum('subtotal');
            $invoiceNumber = 'IV-SVC-'.now()->format('ymd').strtoupper(bin2hex(random_bytes(2)));

            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'warehouse_id' => $request->warehouse_id,
                'date' => now()->toDateString(),
                'total_amount' => $totalAmount,
                'received_amount' => $request->received_amount,
                'change_amount' => max(0, $request->received_amount - totalAmount),
                'payment_method' => $request->payment_method,
                'status' => 'completed',
            ]);

            foreach ($serviceOrder->items as $item) {
                $sale->items()->create([
                    'product_id' => $item->product_id,
                    'unit_id' => $item->unit_id,
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'cost' => 0, // Services usually have 0 direct cost in this context
                    'subtotal' => $item->subtotal,
                ]);
            }

            $serviceOrder->update(['sale_id' => $sale->id]);

            return back()->with('success', 'Pembayaran berhasil diproses.');
        });
    }
}
