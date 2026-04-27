<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickCreateUnitController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:units,name'],
            'symbol' => ['nullable', 'string', 'max:20', 'unique:units,symbol'],
        ]);

        if (empty($validated['symbol'])) {
            $validated['symbol'] = strtolower(substr($validated['name'], 0, 3));
        }

        $unit = Unit::create($validated);

        return response()->json([
            'message' => 'Unit added successfully.',
            'unit' => $unit,
        ]);
    }
}
