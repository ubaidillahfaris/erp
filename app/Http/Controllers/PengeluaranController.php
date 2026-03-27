<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pengeluaran::query();

        if ($request->has('search')) {
            $query->where('nama_pengeluaran', 'like', '%' . $request->search . '%')
                ->orWhere('keterangan', 'like', '%' . $request->search . '%')
                ->orWhere('jenis_pengeluaran', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->input('per_page', 10);

        return inertia('pengeluaran/Index', [
            'pengeluarans' => $query->latest('tanggal')->latest('id')->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function create()
    {
        return inertia('pengeluaran/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jenis_pengeluaran' => ['required', 'string', 'max:255'],
            'nama_pengeluaran' => ['required', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
        ]);

        Pengeluaran::create($validated);

        return redirect()->route('pengeluaran.index')
            ->with('success', 'Catatan pengeluaran berhasil disimpan.');
    }

    public function show(Pengeluaran $pengeluaran)
    {
        // Not used at the moment
    }

    public function edit(Pengeluaran $pengeluaran)
    {
        // Not used at the moment
    }

    public function update(Request $request, Pengeluaran $pengeluaran)
    {
        // Not used at the moment
    }

    public function destroy(Pengeluaran $pengeluaran)
    {
        $pengeluaran->delete();

        return redirect()->route('pengeluaran.index')
            ->with('success', 'Catatan pengeluaran berhasil dihapus.');
    }
}
