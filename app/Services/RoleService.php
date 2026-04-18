<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RoleService
{
    /**
     * Get authorized menus for a user with caching and eager loading.
     */
    public function getAuthorizedMenus(User $user)
    {
        $cacheKey = 'user_menus_'.$user->id;

        return Cache::remember($cacheKey, now()->addDay(), function () use ($user) {
            // 0. Ensure user roles are loaded for reliable checking
            if (!$user->relationLoaded('roles')) {
                $user->load('roles');
            }

            // 1. Get all root menus with their children and modules
            $menus = Menu::with(['children' => function ($query) {
                $query->active()->orderBy('order_priority');
            }, 'module'])
                ->root()
                ->active()
                ->orderBy('order_priority')
                ->get();

            // 2. Identify authorized menu IDs for filtering (if not superadmin)
            $authorizedMenuIds = [];
            if (!$user->hasRole('superadmin')) {
                $authorizedMenuIds = \DB::table('menu_role')
                    ->whereIn('role_id', $user->roles->pluck('id'))
                    ->pluck('menu_id')
                    ->toArray();
            }

            // 3. Filter menus based on user permissions/authorization
            $filteredMenus = $this->filterMenusByStatus($menus, $user, $authorizedMenuIds);

            // 4. GROUPING LOGIC
            // Get authorized modules for the user
            if ($user->hasRole('superadmin')) {
                $authorizedModules = \App\Models\Module::active()->orderBy('order_priority')->get();
            } else {
                $authorizedModules = \App\Models\Module::active()
                    ->whereHas('roles', function ($query) use ($user) {
                        $query->whereIn('roles.id', $user->roles->pluck('id'));
                    })
                    ->orderBy('order_priority')
                    ->get();
            }

            // Group filtered menus by module_id
            $grouped = $filteredMenus->groupBy('module_id');

            $result = [];

            // Add authorized modules that have at least one authorized menu
            foreach ($authorizedModules as $module) {
                $moduleMenus = $grouped->get($module->id, collect());
                
                // Show module if there are menus, or if user is superadmin (show empty shell if requested)
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
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

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
