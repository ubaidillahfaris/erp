<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Module;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ServiceMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Modules exist
        $transaksiModule = Module::where('slug', 'transaksi')->first();
        $settingsModule = Module::where('slug', 'settings')->first();

        // 2. Define Menus
        $menus = [
            // TRANSAKSI SECTION
            [
                'route_name' => 'service-orders.create',
                'name' => 'Service POS',
                'path' => '/service-orders/create',
                'icon' => 'ShoppingCart',
                'permission_name' => 'make sales',
                'module_id' => $transaksiModule?->id,
                'order_priority' => 50,
            ],
            [
                'route_name' => 'service-orders.index',
                'name' => 'Manajemen Order',
                'path' => '/service-orders',
                'icon' => 'ClipboardList',
                'permission_name' => 'make sales',
                'module_id' => $transaksiModule?->id,
                'order_priority' => 51,
            ],

            // SETTINGS SECTION
            [
                'route_name' => 'settings.services.index',
                'name' => 'Katalog Jasa',
                'path' => '/settings/services',
                'icon' => 'PackageOpen',
                'permission_name' => 'manage services',
                'module_id' => $settingsModule?->id,
                'order_priority' => 90,
            ],
        ];

        // Cleanup old menu
        Menu::where('route_name', 'service-orders.board')->delete();

        foreach ($menus as $menuData) {
            Menu::updateOrCreate(
                ['route_name' => $menuData['route_name']],
                $menuData
            );
        }

        // 3. Define Permissions
        $permissions = [
            'manage services',
            'view service orders',
            'create service orders',
            'edit service orders',
            'void service orders',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm);
        }

        // 4. Assign Permissions to Roles
        $superadmin = Role::where('name', 'superadmin')->first();
        $owner = Role::where('name', 'owner')->first();
        $admin = Role::where('name', 'admin')->first();

        if ($superadmin) {
            $superadmin->givePermissionTo($permissions);
            $allMenuIds = Menu::whereIn('route_name', array_column($menus, 'route_name'))->pluck('id')->toArray();
            $superadmin->menus()->syncWithoutDetaching($allMenuIds);
        }

        if ($owner) {
            $owner->givePermissionTo($permissions);
            $allMenuIds = Menu::whereIn('route_name', array_column($menus, 'route_name'))->pluck('id')->toArray();
            $owner->menus()->syncWithoutDetaching($allMenuIds);
        }

        // Clear Caches
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(RoleService::class)->clearAllMenuCaches();
    }
}
