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
        $cacheKey = 'user_menus_'.$user->id;

        if (app()->runningUnitTests()) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addDay(), function () use ($user) {
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
            $businessType = $user->company?->business_type;
            // allowedModules: ['slug' => '*'] or ['slug' => ['route.name', ...]] or empty []
            $allowedModules = $businessType ? config("business_presets.{$businessType}.modules") : [];

            foreach ($modules as $module) {
                // If it's not a superadmin, filter modules based on business preset
                if (! $user->hasRole('superadmin') && ! array_key_exists($module->slug, $allowedModules)) {
                    continue;
                }

                $moduleMenus = $grouped->get($module->id, collect());

                // For non-superadmin: apply per-menu granularity if the preset specifies route names
                if (! $user->hasRole('superadmin')) {
                    $allowedRoutes = $allowedModules[$module->slug] ?? '*';
                    if ($allowedRoutes !== '*' && is_array($allowedRoutes)) {
                        $moduleMenus = $moduleMenus->filter(function ($menu) use ($allowedRoutes) {
                            $routeName = $menu->route_name ?? null;

                            return in_array($routeName, $allowedRoutes) || array_key_exists($routeName, $allowedRoutes);
                        })->map(function ($menu) use ($allowedRoutes) {
                            $routeName = $menu->route_name ?? null;
                            $options = $allowedRoutes[$routeName] ?? null;

                            if ($options) {
                                if (is_string($options)) {
                                    $menu->name = $options;
                                } elseif (is_array($options)) {
                                    if (isset($options['name'])) {
                                        $menu->name = $options['name'];
                                    }
                                    if (isset($options['icon'])) {
                                        $menu->icon = $options['icon'];
                                    }
                                }
                            }

                            return $menu;
                        });
                    }
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

            // If not superadmin, check if "general" is an allowed module
            if (! $user->hasRole('superadmin') && ! array_key_exists('general', $allowedModules)) {
                $generalMenus = collect();
            }

            if ($generalMenus->isNotEmpty()) {
                // Apply aliasing for General module if needed
                if (! $user->hasRole('superadmin')) {
                    $allowedRoutes = $allowedModules['general'] ?? '*';
                    if ($allowedRoutes !== '*' && is_array($allowedRoutes)) {
                        $generalMenus = $generalMenus->map(function ($menu) use ($allowedRoutes) {
                            $routeName = $menu->route_name ?? null;
                            $options = $allowedRoutes[$routeName] ?? null;

                            if ($options) {
                                if (is_string($options)) {
                                    $menu->name = $options;
                                } elseif (is_array($options)) {
                                    if (isset($options['name'])) {
                                        $menu->name = $options['name'];
                                    }
                                    if (isset($options['icon'])) {
                                        $menu->icon = $options['icon'];
                                    }
                                }
                            }

                            return $menu;
                        });
                    }
                }

                $result[] = [
                    'id' => null,
                    'name' => 'General',
                    'slug' => 'general',
                    'icon' => 'layout-grid',
                    'menus' => $generalMenus->values()->toArray(),
                ];
            }

            return $result;
        });
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
            if (! in_array($menu->id, $authorizedMenuIds)) {
                return false;
            }

            // 2. Secondary check: If menu has a permission name, verify user has it
            if ($menu->permission_name && ! $user->can($menu->permission_name)) {
                return false;
            }

            // 3. If it's a parent, also filter its children
            if ($menu->children->isNotEmpty()) {
                $menu->setRelation('children', $this->filterMenusByStatus($menu->children, $user, $authorizedMenuIds));
            }

            return true;
        })->values();
    }
}
