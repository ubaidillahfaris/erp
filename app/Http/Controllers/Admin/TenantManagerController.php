<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Menu;
use App\Models\Tier;
use App\Models\TierFeature;
use App\Models\CompanyFeatureOverride;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantManagerController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::with('tier');

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

        $company->flushFeatureCache();
        app(\App\Services\RoleService::class)->clearAllMenuCaches();

        return back()->with('success', "Tier for {$company->name} updated.");
    }

    public function showOverrides(Company $company, Request $request)
    {
        $overrides = CompanyFeatureOverride::where('company_id', $company->id)
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        // Attach human-readable menu names to overrides
        $overrides->getCollection()->transform(function ($override) {
            $menu = Menu::where('feature_key', $override->feature_key)
                ->orWhere('route_name', $override->feature_key)
                ->first();
            
            $override->feature_name = $menu ? $menu->name : $override->feature_key;
            return $override;
        });

        // Get all available features to choose from (all menus)
        $allFeatures = Menu::with('module:id,name')
            ->get()
            ->map(function ($menu) {
                return [
                    'name' => $menu->name,
                    'feature_key' => $menu->feature_key ?? $menu->route_name,
                    'module_name' => $menu->module ? $menu->module->name : 'General',
                ];
            })
            ->groupBy('module_name');

        return Inertia::render('Admin/System/Tenants/Overrides', [
            'company' => $company,
            'overrides' => $overrides,
            'availableFeatures' => $allFeatures,
            'filters' => $request->only(['per_page']),
        ]);
    }

    public function bulkDestroyOverride(Request $request, Company $company)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:company_feature_overrides,id',
        ]);

        CompanyFeatureOverride::where('company_id', $company->id)
            ->whereIn('id', $request->ids)
            ->delete();

        $company->flushFeatureCache();
        app(\App\Services\RoleService::class)->clearAllMenuCaches();

        return back()->with('success', 'Selected overrides removed.');
    }

    public function storeOverride(Request $request, Company $company)
    {
        $request->validate([
            'feature_key' => 'required|string',
            'is_enabled' => 'required|boolean',
            'expires_at' => 'nullable|date',
        ]);

        // Ensure menu has feature_key
        $menu = Menu::where('feature_key', $request->feature_key)
            ->orWhere('route_name', $request->feature_key)
            ->first();

        if ($menu && !$menu->feature_key) {
            $menu->update(['feature_key' => $request->feature_key]);
        }

        CompanyFeatureOverride::updateOrCreate(
            ['company_id' => $company->id, 'feature_key' => $request->feature_key],
            [
                'is_enabled' => $request->is_enabled,
                'expires_at' => $request->expires_at,
            ]
        );

        $company->flushFeatureCache();
        app(\App\Services\RoleService::class)->clearAllMenuCaches();

        return back()->with('success', 'Feature override updated.');
    }

    public function destroyOverride(Company $company, CompanyFeatureOverride $override)
    {
        $override->delete();
        
        $company->flushFeatureCache();
        app(\App\Services\RoleService::class)->clearAllMenuCaches();

        return back()->with('success', 'Feature override removed.');
    }
}
