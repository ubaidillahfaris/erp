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
        // Get all unique feature keys defined in menus, grouped by module
        $availableFeatures = Menu::whereNotNull('feature_key')
            ->select('feature_key', 'module_id', 'name')
            ->with('module:id,name')
            ->get()
            ->groupBy(fn($item) => $item->module ? $item->module->name : 'General');

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

        // Clear existing and re-add
        $tier->features()->delete();

        foreach ($request->features as $featureKey) {
            // Find the module_id for this feature from menus
            $menu = Menu::where('feature_key', $featureKey)->first();

            TierFeature::create([
                'tier_id' => $tier->id,
                'feature_key' => $featureKey,
                'module_id' => $menu?->module_id,
            ]);
        }

        return redirect()->route('admin.tiers.index')->with('success', "Features for {$tier->name} updated.");
    }
}
