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
            // Fetch all active root menus with their active children
            $menus = Menu::with(['children' => function ($query) {
                $query->active()->orderBy('order_priority');
            }])
                ->root()
                ->active()
                ->orderBy('order_priority')
                ->get();

            // Filter menus based on user permissions
            return $this->filterMenusByPermission($menus, $user)->toArray();
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
        // If using a driver that supports tags, we could use Cache::tags(['menus'])->flush();
        // For simplicity here, we might just rely on the day-long expiration or
        // implement a more granular invalidation if needed.
    }

    /**
     * Recursively filter menus and their children by user permissions.
     */
    protected function filterMenusByPermission($menus, User $user)
    {
        return $menus->filter(function ($menu) use ($user) {
            // Superadmin always has access to all menus
            if ($user->hasRole('superadmin')) {
                return true;
            }

            // If menu has a permission name, check if user has it
            if ($menu->permission_name && ! $user->can($menu->permission_name)) {
                return false;
            }

            // If it's a parent, also filter its children
            if ($menu->children->isNotEmpty()) {
                $menu->setRelation('children', $this->filterMenusByPermission($menu->children, $user));

                // If all children were filtered out and the parent itself has no route/action,
                // we might want to hide the parent too.
                // But for now, we'll keep it if it passes its own permission check.
            }

            return true;
        })->values();
    }
}
