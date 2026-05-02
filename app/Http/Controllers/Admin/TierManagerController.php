<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tier;
use App\Models\TierFeature;
use App\Models\Module;
use App\Models\Menu;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TierManagerController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/System/Tiers/Index', [
            'tiers' => Tier::withCount('features')->get(),
        ]);
    }

    public function showFeatures(Tier $tier)
    {
        // Get ALL menus in the system, grouped by module
        // We use route_name as a fallback for feature_key if it's missing
        $availableFeatures = Menu::with('module:id,name')
            ->get()
            ->map(function($menu) {
                return [
                    'name' => $menu->name,
                    'feature_key' => $menu->feature_key ?? $menu->route_name,
                    'module_name' => $menu->module ? $menu->module->name : 'General',
                ];
            })
            ->groupBy('module_name');

        // Current features for this tier
        $currentFeatures = $tier->features()->pluck('feature_key')->toArray();

        return Inertia::render('Admin/System/Tiers/Features', [
            'tier' => $tier,
            'availableFeatures' => $availableFeatures,
            'currentFeatures' => $currentFeatures,
        ]);
    }

    public function syncFeatures(Request $request, Tier $tier)
    {
        $request->validate([
            'features' => 'array',
            'features.*' => 'string',
        ]);

        // Clear existing
        $tier->features()->delete();

        foreach ($request->features as $featureKey) {
            // Find if this is an existing feature_key or a route_name
            $menu = Menu::where('feature_key', $featureKey)
                ->orWhere('route_name', $featureKey)
                ->first();

            // If the menu didn't have a feature_key, let's assign it now so the logic works globally
            if ($menu && !$menu->feature_key) {
                $menu->update(['feature_key' => $featureKey]);
            }

            TierFeature::create([
                'tier_id' => $tier->id,
                'feature_key' => $featureKey,
                'module_id' => $menu?->module_id,
            ]);
        }

        return redirect()->route('admin.tiers.index')->with('success', "Features for {$tier->name} updated.");
    }
}
