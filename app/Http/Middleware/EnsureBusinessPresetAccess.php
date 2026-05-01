<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessPresetAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if not logged in or is superadmin (Superadmin bypasses business logic)
        if (! $user || $user->hasRole('superadmin')) {
            return $next($request);
        }

        $company = $user->company;

        // Skip if no company (auth/onboarding middleware will handle this)
        if (! $company || ! $company->business_type) {
            return $next($request);
        }

        $currentRouteName = Route::currentRouteName();

        // Skip if no route name (e.g., internal routes)
        if (! $currentRouteName) {
            return $next($request);
        }

        $businessType = strtolower($company->business_type);
        $presets = config('business_presets');
        $preset = $presets[$businessType] ?? null;

        // If no preset defined for this business type, allow everything (fail-safe)
        if (! $preset) {
            return $next($request);
        }

        // 1. Handle "POS" redirection for different business types
        // If a business has a specific default_route (like Service POS), redirect them there if they try to access the standard POS.
        if ($currentRouteName === 'pos.index') {
            if (isset($preset['default_route']) && $preset['default_route'] !== 'pos.index') {
                return redirect()->route($preset['default_route']);
            }
        }

        // 2. Find the menu matching this route name to identify its module
        $menu = Menu::with('module')->whereRaw('LOWER(route_name) = ?', [strtolower($currentRouteName)])->first();

        // If the route is not a managed menu, we allow it (e.g., profile, logout, etc.)
        if (! $menu) {
            return $next($request);
        }

        $moduleSlug = $menu->module?->slug;
        $allowedModules = $preset['modules'] ?? [];

        // 3. Check Module-level access
        if (! array_key_exists($moduleSlug, $allowedModules)) {
            return $this->handleUnauthorized($request, $preset);
        }

        $moduleAccess = $allowedModules[$moduleSlug];

        // 4. Check Route-level access within the module
        if ($moduleAccess === '*') {
            return $next($request);
        }

        if (is_array($moduleAccess)) {
            // Check if route name exists as key (assoc) or value (list)
            $isAllowed = array_key_exists($currentRouteName, $moduleAccess) || in_array($currentRouteName, $moduleAccess);
            
            if ($isAllowed) {
                return $next($request);
            }
        }

        return $this->handleUnauthorized($request, $preset);
    }

    /**
     * Handle unauthorized access.
     */
    protected function handleUnauthorized(Request $request, $preset = null)
    {
        $defaultRoute = $preset['default_route'] ?? 'dashboard';
        $currentRouteName = Route::currentRouteName();

        if ($request->header('X-Inertia')) {
            // Prevent redirect loop
            if ($currentRouteName === $defaultRoute) {
                abort(403, 'Fitur ini tidak tersedia untuk tipe bisnis Anda.');
            }

            return redirect()->route($defaultRoute)->with('error', 'Fitur ini tidak tersedia untuk tipe bisnis Anda.');
        }

        abort(403, 'Fitur ini tidak tersedia untuk tipe bisnis Anda.');
    }
}
