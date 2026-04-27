<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function index()
    {
        return Inertia::render('Warehouses/Index', [
            'warehouses' => Warehouse::orderBy('is_default', 'desc')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:warehouses,code',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Warehouse::create($validated);

        return redirect()->back()->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:warehouses,code,'.$warehouse->id,
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $warehouse->update($validated);

        return redirect()->back()->with('success', 'Gudang berhasil diperbarui.');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->is_default) {
            return redirect()->back()->with('error', 'Gudang utama tidak dapat dihapus.');
        }

        // Check if there is any stock in this warehouse
        if (Stock::where('warehouse_id', $warehouse->id)->where('balance', '>', 0)->exists()) {
            return redirect()->back()->with('error', 'Gudang tidak dapat dihapus karena masih memiliki stok barang.');
        }

        $warehouse->delete();

        return redirect()->back()->with('success', 'Gudang berhasil dihapus.');
    }
}
