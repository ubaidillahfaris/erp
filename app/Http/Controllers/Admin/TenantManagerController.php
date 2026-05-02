<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Tier;
use App\Models\TierFeature;
use App\Models\CompanyFeatureOverride;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantManagerController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query()->with(['tier']);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('business_type', 'like', '%' . $request->search . '%');
        }

        $tenants = $query->paginate($request->per_page ?? 10)
                         ->withQueryString();

        return Inertia::render('Admin/System/Tenants/Index', [
            'tenants' => $tenants,
            'tiers' => Tier::all(),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function updateTier(Request $request, Company $company)
    {
        $request->validate([
            'tier_id' => 'required|exists:tiers,id',
        ]);

        $company->update([
            'tier_id' => $request->tier_id,
        ]);

        return back()->with('success', "Tier for {$company->name} updated.");
    }

    public function showOverrides(Company $company)
    {
        $overrides = CompanyFeatureOverride::where('company_id', $company->id)->get();
        
        // Get all available features to choose from
        $allFeatures = TierFeature::select('feature_key', 'module_id')
            ->with('module:id,name')
            ->get()
            ->groupBy('module.name');

        return Inertia::render('Admin/System/Tenants/Overrides', [
            'company' => $company,
            'overrides' => $overrides,
            'availableFeatures' => $allFeatures,
        ]);
    }

    public function storeOverride(Request $request, Company $company)
    {
        $request->validate([
            'feature_key' => 'required|string',
            'is_enabled' => 'required|boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        CompanyFeatureOverride::updateOrCreate(
            ['company_id' => $company->id, 'feature_key' => $request->feature_key],
            ['is_enabled' => $request->is_enabled, 'expires_at' => $request->expires_at]
        );

        return back()->with('success', 'Feature override updated.');
    }

    public function destroyOverride(Company $company, CompanyFeatureOverride $override)
    {
        $override->delete();
        return back()->with('success', 'Override removed.');
    }
}
