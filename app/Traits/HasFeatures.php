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

        // NO CACHE - REAL TIME CHECK
        // 1. Check Override (Real-time Expiry) - OVERRIDE IS KING
        $override = CompanyFeatureOverride::where('company_id', $company->id)
            ->where('feature_key', $featureKey)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($override) {
            return (bool) $override->is_enabled;
        }

        // 2. Check if the key itself is a Module Slug and check its global status
        $module = \App\Models\Module::where('slug', $featureKey)->first();
        if ($module && !$module->is_active) {
            return false;
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
    }

    /**
     * Increment the cache version for a company to effectively flush its feature cache.
     */
    public function flushFeatureCache(): void
    {
        $companyId = $this instanceof \App\Models\Company ? $this->id : $this->company_id;
        
        if ($companyId) {
            Cache::increment("company_{$companyId}_cache_version");
        }
    }
}
