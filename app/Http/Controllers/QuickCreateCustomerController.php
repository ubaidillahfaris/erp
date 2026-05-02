<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerStatus;
use App\Models\CustomerType;
use Illuminate\Http\Request;

class QuickCreateCustomerController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        $customer = Customer::create(array_merge($validated, [
            'customer_type_id' => CustomerType::where('name', 'Regular')->first()?->id ?? 1,
            'customer_status_id' => CustomerStatus::where('name', 'Active')->first()?->id ?? 1,
        ]));

        return response()->json([
            'message' => 'Customer added successfully.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
            ],
        ]);
    }
}
