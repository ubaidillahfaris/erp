<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort') ?: 'date';
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $query = Pengeluaran::query();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_pengeluaran', 'like', '%'.$request->search.'%')
                    ->orWhere('notes', 'like', '%'.$request->search.'%')
                    ->orWhere('jenis_pengeluaran', 'like', '%'.$request->search.'%');
            });
        }

        $pengeluarans = $query->with('account')
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        $accounts = Account::where('type', 'expense')->orderBy('code')->get();

        return inertia('pengeluaran/Index', [
            'pengeluarans' => $pengeluarans,
            'accounts' => $accounts,
            'filters' => $request->only(['search', 'per_page', 'sort', 'direction']),
        ]);
    }

    public function create()
    {
        $accounts = Account::where('type', 'expense')->orderBy('code')->get();

        return inertia('pengeluaran/Create', [
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'jenis_pengeluaran' => ['required', 'string', 'max:255'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'nama_pengeluaran' => ['required', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
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
            ->with('success', 'Catatan pengeluaran deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:pengeluarans,id',
        ]);

        Pengeluaran::whereIn('id', $request->ids)->delete();

        return to_route('pengeluaran.index')->with('success', count($request->ids).' catatan pengeluaran deleted successfully.');
    }
}
