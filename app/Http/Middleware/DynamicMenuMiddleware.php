<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Services\RoleService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class DynamicMenuMiddleware
{
    public function __construct(protected RoleService $roleService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If not logged in, pass to next middleware (auth will handle it)
        if (! $user) {
            return $next($request);
        }

        $currentRouteName = Route::currentRouteName();

        // Skip if no route name (e.g., some internal routes)
        if (! $currentRouteName) {
            return $next($request);
        }

        // 1. Find the menu matching this route name (case-insensitive)
        $menu = Menu::whereRaw('LOWER(route_name) = ?', [strtolower($currentRouteName)])->first();

        // 2. If the route is a managed menu item, check authorization
        if ($menu) {
            if ($menu->permission_name && ! $user->can($menu->permission_name)) {
                // If it's an AJAX/Inertia request, return 403 or redirect
                if ($request->header('X-Inertia')) {
                    return $this->handleUnauthorized($request, $user);
                }

                abort(403, 'Akses ditolak.');
            }
        }

        // 3. Special Case: Home / Dashboard redirect
        if ($currentRouteName === 'dashboard' || $request->is('/')) {
            if (! $user->can('view dashboard')) {
                return redirect()->route('pos.index');
            }
        }

        return $next($request);
    }

    /**
     * Handle unauthorized access with a smart redirect.
     */
    protected function handleUnauthorized(Request $request, $user)
    {
        // For Inertia, we can redirect to a custom 403 page or the first allowed menu
        // But for now, let's just redirect to POS if they have access, or abort.
        if ($user->can('make sales')) {
            return redirect()->route('pos.index')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        }

        abort(403, 'Akses ditolak.');
    }
}
