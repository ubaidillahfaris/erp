<?php

namespace App\Http\Controllers;

use App\Actions\RecalculateHpp;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort') ?: 'created_at';
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $units = Unit::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%");
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return inertia('unit/Index', [
            'units' => $units,
            'filters' => $request->only(['search', 'per_page', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('unit/Create', [
            'allUnits' => Unit::all(['id', 'name', 'symbol']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUnitRequest $request)
    {
        $validated = $request->validated();

        if (empty($validated['symbol'])) {
            $validated['symbol'] = strtolower(substr($validated['name'], 0, 3));
        }

        $unit = Unit::create($validated);

        if (isset($validated['conversions'])) {
            foreach ($validated['conversions'] as $conv) {
                $unit->conversions()->create([
                    'target_unit_id' => $conv['target_unit_id'],
                    'ratio' => $conv['ratio'],
                ]);
            }
        }

        return redirect()->route('unit.index')
            ->with('success', 'Unit added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit)
    {
        return inertia('unit/Show', [
            'unit' => $unit,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit)
    {
        return inertia('unit/Edit', [
            'unit' => $unit->load('conversions'),
            'allUnits' => Unit::where('id', '!=', $unit->id)->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        $validated = $request->validated();
        $unit->update($validated);

        // Sync conversions
        if (isset($validated['conversions'])) {
            $unit->conversions()->delete();
            foreach ($validated['conversions'] as $conv) {
                $unit->conversions()->create([
                    'target_unit_id' => $conv['target_unit_id'],
                    'ratio' => $conv['ratio'],
                ]);
            }
        } else {
            $unit->conversions()->delete();
        }

        // Potential ratio change: trigger HPP recalculation for all manufactured products
        // This is a "heavy" but safe way to ensure global consistency.
        Product::whereIn('type', ['semi_finished', 'finished_good'])
            ->get()
            ->each(fn ($p) => app(RecalculateHpp::class)->handle($p));

        return redirect()->route('unit.index')
            ->with('success', 'Unit updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        if ($unit->products()->exists()) {
            return back()->with('error', 'Unit cannot be deleted because it is used by a product.');
        }

        $unit->delete();

        return redirect()->route('unit.index')
            ->with('success', 'Unit deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:units,id',
        ]);

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($request->ids as $id) {
            $unit = Unit::find($id);
            if ($unit && ! $unit->products()->exists()) {
                $unit->delete();
                $deletedCount++;
            } else {
                $skippedCount++;
            }
        }

        $message = "{$deletedCount} units deleted successfully.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} units skipped because they are in use.";
        }

        return to_route('unit.index')->with($deletedCount > 0 ? 'success' : 'error', $message);
    }
}
