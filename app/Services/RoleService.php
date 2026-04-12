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
            // 1. Get IDs of menus assigned to any of the user's roles
            $authorizedMenuIds = \DB::table('menu_role')
                ->whereIn('role_id', $user->roles->pluck('id'))
                ->pluck('menu_id')
                ->toArray();

            // 2. Fetch all active root menus with their active children
            $menus = Menu::with(['children' => function ($query) {
                $query->active()->orderBy('order_priority');
            }])
                ->root()
                ->active()
                ->orderBy('order_priority')
                ->get();

            // 3. Filter menus based on authorized list and user permissions
            return $this->filterMenusByStatus($menus, $user, $authorizedMenuIds)->toArray();
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
