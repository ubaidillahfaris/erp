<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerStatus;
use App\Models\CustomerType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort') ?: 'created_at';
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $customers = Customer::query()
            ->with(['type', 'status'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Customer/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'per_page', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Customer/Create', [
            'customerTypes' => CustomerType::all(['id', 'name']),
            'customerStatuses' => CustomerStatus::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'customer_type_id' => 'required|exists:customer_types,id',
            'customer_status_id' => 'required|exists:customer_statuses,id',
        ]);

        Customer::create($request->all());

        return to_route('customer.index')->with('success', 'Customer berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer): Response
    {
        return Inertia::render('Customer/Edit', [
            'customer' => $customer,
            'customerTypes' => CustomerType::all(['id', 'name']),
            'customerStatuses' => CustomerStatus::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'customer_type_id' => 'required|exists:customer_types,id',
            'customer_status_id' => 'required|exists:customer_statuses,id',
        ]);

        $customer->update($request->all());

        return to_route('customer.index')->with('success', 'Customer berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return back()->with('error', 'Customer tidak bisa dihapus karena memiliki riwayat transaksi.');
        }

        $customer->delete();

        return back()->with('success', 'Customer berhasil dihapus.');
    }

    /**
     * Bulk remove the specified resources from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:customers,id',
        ]);

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($request->ids as $id) {
            $customer = Customer::find($id);
            if ($customer && !$customer->sales()->exists()) {
                $customer->delete();
                $deletedCount++;
            } else {
                $skippedCount++;
            }
        }

        $message = "{$deletedCount} customer berhasil dihapus.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} customer dilewati karena memiliki riwayat transaksi.";
        }

        return to_route('customer.index')->with($deletedCount > 0 ? 'success' : 'error', $message);
    }
}
