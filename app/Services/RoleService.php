<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    /**
     * Get authorized menus for a user with caching and eager loading.
     */
    public function getAuthorizedMenus(User $user)
    {
        // NO CACHE - ALWAYS FRESH SIDEBAR
        // 0. Ensure user roles and company are loaded for reliable checking
        if (! $user->relationLoaded('roles') || ! $user->relationLoaded('company')) {
            $user->load(['roles', 'company']);
        }

        // 1. Get all root menus with their children and modules
        $menus = Menu::with(['children' => function ($query) {
            $query->active()->orderBy('order_priority');
        }, 'module'])
            ->root()
            ->active()
            ->orderBy('order_priority')
            ->orderBy('id')
            ->get();

        // 2. Identify authorized menu IDs for filtering (if not superadmin)
        $authorizedMenuIds = [];
        if (! $user->hasRole('superadmin')) {
            $authorizedMenuIds = \DB::table('menu_role')
                ->whereIn('role_id', $user->roles->pluck('id'))
                ->pluck('menu_id')
                ->toArray();
        }

        // 3. Filter menus based on user permissions/authorization
        $filteredMenus = $this->filterMenusByStatus($menus, $user, $authorizedMenuIds);

        // 4. GROUPING LOGIC
        // Group filtered menus by module_id
        $grouped = $filteredMenus->groupBy('module_id');

        $result = [];

        // Get all active modules
        $modules = Module::active()->orderBy('order_priority')->get();

        // Add modules that have at least one authorized menu
        foreach ($modules as $module) {
            // Check if module itself is accessible via feature gate (if module slug is used as feature key)
            // If not superadmin, we check if the company has access to this module
            if (!$user->hasRole('superadmin') && !$user->hasFeature($module->slug)) {
                // We only continue if NO children menus inside this module are overridden to 'granted'
                $hasOverriddenChild = $grouped->get($module->id, collect())->contains(function($menu) use ($user) {
                    $key = $menu->feature_key ?? $menu->route_name;
                    return $key && $user->hasFeature($key);
                });
                
                if (!$hasOverriddenChild) continue;
            }

            $moduleMenus = $grouped->get($module->id, collect());

            // For non-superadmin: apply per-menu feature gate granularity
            if (! $user->hasRole('superadmin')) {
                $moduleMenus = $moduleMenus->filter(function ($menu) use ($user) {
                    // Check if this menu OR any of its children are allowed
                    return $this->isMenuAllowed($menu, $user);
                });
            }

            // Show module if there are menus, or if user is superadmin
            if ($moduleMenus->isNotEmpty() || $user->hasRole('superadmin')) {
                $result[] = [
                    'id' => $module->id,
                    'name' => $module->name,
                    'slug' => $module->slug,
                    'icon' => $module->icon,
                    'menus' => $moduleMenus->values()->toArray(),
                ];
            }
        }

        // 5. virtual "General" module for menus without module_id
        $generalMenus = $grouped->get(null, collect());

        if ($generalMenus->isNotEmpty()) {
            // Filter general menus by feature key for non-superadmins
            if (!$user->hasRole('superadmin')) {
                $generalMenus = $generalMenus->filter(function ($menu) use ($user) {
                    return $this->isMenuAllowed($menu, $user);
                });
            }

            if ($generalMenus->isNotEmpty()) {
                $result[] = [
                    'id' => null,
                    'name' => 'General',
                    'slug' => 'general',
                    'icon' => 'layout-grid',
                    'menus' => $generalMenus->values()->toArray(),
                ];
            }
        }

        return $result;
    }

    /**
     * Check if a menu (or any of its children) is allowed for the user.
     */
    protected function isMenuAllowed($menu, User $user): bool
    {
        $key = $menu->feature_key ?? $menu->route_name;
        
        // If the menu itself is allowed, we're good
        if ($key && $user->hasFeature($key)) {
            return true;
        }

        // If it's a parent, check if any of its children are allowed
        if ($menu->children->isNotEmpty()) {
            foreach ($menu->children as $child) {
                if ($this->isMenuAllowed($child, $user)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sync a role's permissions based on a list of Menu IDs.
     */
    public function syncRolePermissionsFromMenus($role, array $menuIds): void
    {
        // Get permissions linked to the selected menus
        $permissions = Menu::whereIn('id', $menuIds)
            ->whereNotNull('permission_name')
            ->pluck('permission_name')
            ->toArray();

        // Also include permissions from any selected children if the parent was selected
        // (Though our UI usually handles this, we can be safe here)

        $role->syncPermissions($permissions);

        // Sync the menus pivot table for dynamic RBAC
        if (method_exists($role, 'menus')) {
            $role->menus()->sync($menuIds);
        }

        // Invalidate all related caches
        $this->clearAllMenuCaches();
    }

    /**
     * Clear menu cache for a user.
     */
    public function clearMenuCache(User $user): void
    {
        Cache::forget('user_menus_'.$user->id);
    }

    /**
     * Clear menu cache for all users (e.g., when a role's permissions change).
     * In a real app, you might want to be more specific or use cache tags if supported.
     */
    public function clearAllMenuCaches(): void
    {
        // Clear global Spatie permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Surgical clear for all users since user count is low (5 users found in audit)
        User::all()->each(function (User $user) {
            $this->clearMenuCache($user);
        });
    }

    /**
     * Recursively filter menus and their children by authorized IDs and user permissions.
     */
    protected function filterMenusByStatus($menus, User $user, array $authorizedMenuIds)
    {
        return $menus->filter(function ($menu) use ($user, $authorizedMenuIds) {
            // Superadmin always has access to all menus (Safety first!)
            if ($user->hasRole('superadmin')) {
                return true;
            }

            // 1. Check if the menu ID is explicitly assigned to the user's roles
            // BYPASS: If the menu is explicitly OVERRIDDEN as enabled for this tenant, bypass role & permission check
            $key = $menu->feature_key ?? $menu->route_name;
            $hasOverride = \App\Models\CompanyFeatureOverride::where('company_id', $user->company_id)
                ->where('feature_key', $key)
                ->where('is_enabled', true)
                ->exists();

            if (!$hasOverride) {
                if (! in_array($menu->id, $authorizedMenuIds)) {
                    return false;
                }

                if ($menu->permission_name && ! $user->can($menu->permission_name)) {
                    return false;
                }
            }

            // 2. If it's a parent, also filter its children
            if ($menu->children->isNotEmpty()) {
                $menu->setRelation('children', $this->filterMenusByStatus($menu->children, $user, $authorizedMenuIds));
            }

            return true;
        })->values();
    }
}
