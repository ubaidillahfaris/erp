<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class QuickCreateVendorController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vendors,nama'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $vendor = Vendor::create($validated);

        return response()->json([
            'message' => 'Vendor added successfully.',
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
            ],
        ]);
    }
}
