<?php

namespace App\Traits;

use App\Models\CompanyFeatureOverride;
use App\Models\TierFeature;
use Illuminate\Support\Facades\Cache;

trait HasFeatures
{
    /**
     * Check if the company (or user's company) has access to a specific feature.
     * Logic: Permission < Module Active < Tier Default < Company Override
     */
    public function hasFeature(string $featureKey): bool
    {
        $company = $this instanceof \App\Models\Company ? $this : $this->company;

        if (!$company) {
            return false;
        }

        $cacheKey = "company_{$company->id}_feature_{$featureKey}";

        return Cache::remember($cacheKey, 3600, function () use ($company, $featureKey) {
            // 1. Check if the key itself is a Module Slug and check its global status
            $module = \App\Models\Module::where('slug', $featureKey)->first();
            if ($module && !$module->is_active) {
                return false;
            }

            // 2. Check Override (Real-time Expiry)
            $override = CompanyFeatureOverride::where('company_id', $company->id)
                ->where('feature_key', $featureKey)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($override) {
                return (bool) $override->is_enabled;
            }

            // 3. Fallback to Tier Default + Global Module Check
            $tierFeature = TierFeature::with('module')
                ->where('tier_id', $company->tier_id)
                ->where('feature_key', $featureKey)
                ->first();

            if (!$tierFeature) {
                return false;
            }

            // If feature exists in tier, it only works if its module is active globally
            return (bool) ($tierFeature->module?->is_active ?? true);
        });
    }

    /**
     * Check if a module is active for the company.
     */
    public function hasModule(string $moduleSlug): bool
    {
        $company = $this instanceof \App\Models\Company ? $this : $this->company;

        if (!$company) {
            return false;
        }

        // Implementation for module activation check via company_modules table
        return $company->modules()->where('slug', $moduleSlug)->where('is_active', true)->exists();
    }
}
