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
    public function up(): void
    {
        // 1. Create Permissions
        $permissions = [
            'manage warehouses',
            'manage stock transfers',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // 2. Assign to superadmin
        $role = Role::where('name', 'superadmin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }

        // 3. Find Inventory Module
        $inventoryModule = Module::where('slug', 'inventory')->first();

        // 4. Create Menu Entries
        $menus = [
            [
                'route_name' => 'warehouses.index',
                'name' => 'Manajemen Gudang',
                'path' => '/warehouses',
                'icon' => 'Warehouse',
                'permission_name' => 'manage warehouses',
                'group_name' => 'Inventory Control',
                'order_priority' => 125,
                'module_id' => $inventoryModule?->id,
            ],
            [
                'route_name' => 'stock-transfers.index',
                'name' => 'Transfer Stok',
                'path' => '/stock-transfers',
                'icon' => 'ArrowRightLeft',
                'permission_name' => 'manage stock transfers',
                'group_name' => 'Inventory Control',
                'order_priority' => 126,
                'module_id' => $inventoryModule?->id,
            ],
        ];

        $menuIds = [];
        foreach ($menus as $m) {
            $menu = Menu::updateOrCreate(
                ['route_name' => $m['route_name']],
                $m
            );
            $menuIds[] = $menu->id;
        }

        // 5. Link menus to superadmin role
        if ($role && method_exists($role, 'menus')) {
            $role->menus()->syncWithoutDetaching($menuIds);
        }

        // 6. Clear Cache
        app(RoleService::class)->clearAllMenuCaches();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::whereIn('route_name', [
            'warehouses.index',
            'stock-transfers.index',
        ])->delete();
    }
};
