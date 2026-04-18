<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPrice;
use App\Models\Produk;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerPriceController extends Controller
{
    /**
     * Display a listing of the custom prices for a customer.
     */
    public function index(Customer $customer)
    {
        $customerPrices = $customer->customerPrices()
            ->with(['produk', 'satuan', 'histories.changedBy'])
            ->latest()
            ->get();

        return Inertia::render('Customer/Prices', [
            'customer' => $customer,
            'customerPrices' => $customerPrices,
            'produks' => Produk::all(['id', 'nama']),
            'satuans' => Satuan::all(['id', 'nama']),
        ]);
    }

    /**
     * Store a newly created custom price.
     */
    public function store(Request $request, Customer $customer)
    {
        $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'satuan_id' => 'required|exists:satuans,id',
            'custom_price' => 'required|numeric|min:0',
            'valid_until' => 'nullable|date',
        ]);

        $customer->customerPrices()->create([
            'produk_id' => $request->produk_id,
            'satuan_id' => $request->satuan_id,
            'custom_price' => $request->custom_price,
            'valid_until' => $request->valid_until,
            'is_active' => true,
        ]);

        return back()->with('success', 'Harga khusus berhasil ditambahkan.');
    }

    /**
     * Update the specified custom price.
     */
    public function update(Request $request, Customer $customer, CustomerPrice $price)
    {
        $request->validate([
            'custom_price' => 'required|numeric|min:0',
            'valid_until' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $price->update($request->only(['custom_price', 'valid_until', 'is_active']));

        return back()->with('success', 'Harga khusus berhasil diperbarui.');
    }

    /**
     * Remove the specified custom price (soft-delete style).
     */
    public function destroy(Customer $customer, CustomerPrice $price)
    {
        $price->update(['is_active' => false]);

        return back()->with('success', 'Harga khusus berhasil dinonaktifkan.');
    }
}
