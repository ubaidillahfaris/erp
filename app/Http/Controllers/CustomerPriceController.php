<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPrice;
use App\Models\Produk;
use App\Models\Satuan;
use App\Models\CustomerCreditSetting;
use App\Models\CustomerCategoryDiscount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerPriceController extends Controller
{
    /**
     * Display a general listing of all customers and their custom prices.
     */
    public function listAll(Request $request)
    {
        $search = $request->input('search');
        
        $customers = Customer::query()
            ->withCount(['customerPrices as active_prices_count' => function ($query) {
                $query->where('is_active', true);
            }])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        return Inertia::render('Customer/PricingList', [
            'customers' => $customers->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

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
            'creditSetting' => $customer->creditSetting,
            'categoryDiscounts' => $customer->categoryDiscounts()->where('is_active', true)->get(),
            'kategoriList' => Produk::whereNotNull('kategori')->distinct()->pluck('kategori'),
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

    /**
     * Store or update credit settings for a customer.
     */
    public function storeCreditSetting(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'allow_credit' => 'required|boolean',
            'credit_limit' => 'nullable|numeric|min:0',
            'global_discount' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'required|boolean',
        ]);

        $customer->creditSetting()->updateOrCreate(
            ['customer_id' => $customer->id],
            $validated
        );

        return back()->with('success', 'Pengaturan kredit berhasil diperbarui.');
    }

    /**
     * Update credit settings for a customer.
     */
    public function updateCreditSetting(Request $request, Customer $customer)
    {
        return $this->storeCreditSetting($request, $customer);
    }

    /**
     * Store a new category discount for a customer.
     */
    public function storeCategoryDiscount(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'kategori' => 'required|string',
            'discount_rate' => 'required|numeric|min:0|max:100',
        ]);

        $customer->categoryDiscounts()->create([
            'kategori' => $validated['kategori'],
            'discount_rate' => $validated['discount_rate'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Diskon kategori berhasil ditambahkan.');
    }

    /**
     * Remove a category discount.
     */
    public function destroyCategoryDiscount(Customer $customer, CustomerCategoryDiscount $discount)
    {
        $discount->delete();

        return back()->with('success', 'Diskon kategori berhasil dihapus.');
    }
}
