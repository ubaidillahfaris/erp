<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use Illuminate\Http\Request;

class QuickCreateSatuanController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', 'unique:satuans,nama'],
            'simbol' => ['nullable', 'string', 'max:20', 'unique:satuans,simbol'],
        ]);

        if (empty($validated['simbol'])) {
            $validated['simbol'] = strtolower(substr($validated['nama'], 0, 3));
        }

        $satuan = Satuan::create($validated);

        return response()->json([
            'message' => 'Satuan berhasil ditambahkan.',
            'satuan' => $satuan,
        ]);
    }
}
