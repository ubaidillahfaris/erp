<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function show()
    {
        if (Auth::user()->company_id) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/Onboarding');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'business_type' => 'required|string|in:laundry,warkop,retail,bengkel,service,other',
        ]);

        $company = Company::create([
            'name' => $request->name,
            'business_type' => $request->business_type,
            'owner_id' => Auth::id(),
        ]);

        Auth::user()->update([
            'company_id' => $company->id,
        ]);

        app(RoleService::class)->clearMenuCache(Auth::user());

        return redirect()->route('dashboard');
    }
}
