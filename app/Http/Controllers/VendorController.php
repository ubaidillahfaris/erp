<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort') ?: 'created_at';
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $vendors = Vendor::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Vendor/Index', [
            'vendors' => $vendors,
            'filters' => $request->only(['search', 'per_page', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Vendor/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        Vendor::create($request->all());

        return to_route('vendor.index')->with('success', 'Vendor added successfully.');
    }

    public function edit(Vendor $vendor): Response
    {
        return Inertia::render('Vendor/Edit', [
            'vendor' => $vendor,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        $vendor->update($request->all());

        return to_route('vendor.index')->with('success', 'Vendor updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vendor $vendor)
    {
        if ($vendor->restocks()->exists()) {
            return back()->with('error', 'Vendor tidak bisa dihapus karena memiliki riwayat pembelian.');
        }

        $vendor->delete();

        return back()->with('success', 'Vendor deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:vendors,id',
        ]);

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($request->ids as $id) {
            $vendor = Vendor::find($id);
            if ($vendor && ! $vendor->restocks()->exists()) {
                $vendor->delete();
                $deletedCount++;
            } else {
                $skippedCount++;
            }
        }

        $message = "{$deletedCount} vendor deleted successfully.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} vendor dilewati karena memiliki riwayat.";
        }

        return to_route('vendor.index')->with($deletedCount > 0 ? 'success' : 'error', $message);
    }
}
