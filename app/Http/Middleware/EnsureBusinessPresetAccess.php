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
     * This middleware now relies entirely on the dynamic Feature Gating system (Tiers & Overrides).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if not logged in or is superadmin
        if (! $user || $user->hasRole('superadmin')) {
            return $next($request);
        }

        $currentRouteName = Route::currentRouteName();

        // Skip if no route name (e.g., internal routes or non-named routes)
        if (! $currentRouteName) {
            return $next($request);
        }

        // 1. Find the menu matching this route name
        $menu = Menu::with('module')->whereRaw('LOWER(route_name) = ?', [strtolower($currentRouteName)])->first();

        // If the route is not a managed menu, we allow it (e.g., profile, logout, etc.)
        if (! $menu) {
            return $next($request);
        }

        // 2. Check if the user has access to this feature via Tier or Override
        $key = $menu->feature_key ?? $menu->route_name;
        
        if ($user->hasFeature($key)) {
            return $next($request);
        }

        // 3. Fallback: If not allowed, handle unauthorized
        return $this->handleUnauthorized($request);
    }

    /**
     * Handle unauthorized access.
     */
    protected function handleUnauthorized(Request $request)
    {
        return \Inertia\Inertia::render('Error', [
            'status' => 403,
            'message' => 'Fitur ini tidak tersedia untuk paket langganan Anda.'
        ])->toResponse($request)->setStatusCode(403);
    }
}
