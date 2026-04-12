<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class QuickCreateVendorController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', 'unique:vendors,nama'],
            'alamat' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $vendor = Vendor::create($validated);

        return response()->json([
            'message' => 'Vendor berhasil ditambahkan.',
            'vendor' => [
                'id' => $vendor->id,
                'nama' => $vendor->nama,
            ],
        ]);
    }
}
