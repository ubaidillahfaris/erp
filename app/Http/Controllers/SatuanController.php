<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSatuanRequest;
use App\Http\Requests\UpdateSatuanRequest;
use App\Models\Satuan;
use Inertia\Inertia;

class SatuanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request('per_page', 10);

        $satuans = Satuan::query()
            ->when(request('search'), function ($query, $search) {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('simbol', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return inertia('satuan/Index', [
            'satuans' => $satuans,
            'filters' => request()->only(['search', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('satuan/Create', [
            'allSatuans' => Satuan::all(['id', 'nama', 'simbol']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSatuanRequest $request)
    {
        $validated = $request->validated();

        if (empty($validated['simbol'])) {
            $validated['simbol'] = strtolower(substr($validated['nama'], 0, 3));
        }

        $satuan = Satuan::create($validated);

        if (isset($validated['conversions'])) {
            foreach ($validated['conversions'] as $conv) {
                $satuan->conversions()->create([
                    'to_satuan_id' => $conv['to_satuan_id'],
                    'rasio' => $conv['rasio'],
                ]);
            }
        }

        return redirect()->route('satuan.index')
            ->with('success', 'Satuan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Satuan $satuan)
    {
        return inertia('satuan/Show', [
            'satuan' => $satuan,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Satuan $satuan)
    {
        return inertia('satuan/Edit', [
            'satuan' => $satuan->load('conversions'),
            'allSatuans' => Satuan::where('id', '!=', $satuan->id)->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSatuanRequest $request, Satuan $satuan)
    {
        $validated = $request->validated();
        $satuan->update($validated);

        // Sync conversions
        if (isset($validated['conversions'])) {
            $satuan->conversions()->delete();
            foreach ($validated['conversions'] as $conv) {
                $satuan->conversions()->create([
                    'to_satuan_id' => $conv['to_satuan_id'],
                    'rasio' => $conv['rasio'],
                ]);
            }
        } else {
            $satuan->conversions()->delete();
        }

        // Potential ratio change: trigger HPP recalculation for all manufactured products
        // This is a "heavy" but safe way to ensure global consistency.
        \App\Models\Produk::whereIn('type', ['semi_finished', 'finished_good'])
            ->get()
            ->each(fn($p) => app(\App\Actions\RecalculateHpp::class)->handle($p));

        return redirect()->route('satuan.index')
            ->with('success', 'Satuan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Satuan $satuan)
    {
        $satuan->delete();

        return redirect()->route('satuan.index')
            ->with('success', 'Satuan berhasil dihapus.');
    }
}
