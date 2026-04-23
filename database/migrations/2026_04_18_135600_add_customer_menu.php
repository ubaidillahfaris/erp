<?php

use App\Models\Menu;
use App\Models\Module;
use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function run(): void
    {
        // 1. Ensure permission exists
        $permissionName = 'manage customers';
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);

        // 2. Assign to superadmin
        $role = Role::where('name', 'superadmin')->first();
        if ($role) {
            $role->givePermissionTo($permissionName);
        }

        // 3. Find CRM Module
        $crmModule = Module::where('slug', 'crm')->first();

        // 4. Create Menu Entry
        $menu = Menu::updateOrCreate(
            ['route_name' => 'customer.index'],
            [
                'name' => 'Master Customer',
                'path' => '/customers',
                'icon' => 'Users',
                'permission_name' => $permissionName,
                'group_name' => 'Master Data',
                'order_priority' => 15,
                'module_id' => $crmModule?->id,
            ]
        );

        // 5. Link menu to superadmin role in menu_role pivot if exists
        if ($role && method_exists($role, 'menus')) {
            $role->menus()->syncWithoutDetaching([$menu->id]);
        }

        // 6. Clear Cache
        app(RoleService::class)->clearAllMenuCaches();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::where('route_name', 'customer.index')->delete();
    }
};
