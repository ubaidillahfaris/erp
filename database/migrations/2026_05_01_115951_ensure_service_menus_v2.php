<?php

use App\Models\Menu;
use App\Models\Module;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure Transaksi Module exists
        $transaksiModule = Module::firstOrCreate(
            ['slug' => 'transaksi'],
            [
                'name' => 'Transaksi',
                'icon' => 'ShoppingCart',
                'order_priority' => 10,
                'is_active' => true,
            ]
        );

        // 2. Ensure Settings Module exists
        $settingsModule = Module::where('slug', 'settings')->first();

        // 3. Add Service Order Menus to Transaksi
        $menus = [
            [
                'route_name' => 'service-orders.create',
                'name' => 'Service POS',
                'path' => '/service-orders/create',
                'icon' => 'ShoppingCart',
                'permission_name' => 'make sales',
                'module_id' => $transaksiModule->id,
                'order_priority' => 20,
            ],
            [
                'route_name' => 'service-orders.board',
                'name' => 'Pipeline Order',
                'path' => '/service-orders/board',
                'icon' => 'ClipboardList',
                'permission_name' => 'make sales',
                'module_id' => $transaksiModule->id,
                'order_priority' => 21,
            ],
            [
                'route_name' => 'service-orders.index',
                'name' => 'Daftar Order',
                'path' => '/service-orders',
                'icon' => 'HistoryIcon',
                'permission_name' => 'make sales',
                'module_id' => $transaksiModule->id,
                'order_priority' => 22,
            ],
        ];

        foreach ($menus as $menuData) {
            Menu::updateOrCreate(
                ['route_name' => $menuData['route_name']],
                $menuData
            );
        }

        // 4. Add Service Catalog to Settings
        if ($settingsModule) {
            Menu::updateOrCreate(
                ['route_name' => 'settings.services.index'],
                [
                    'name' => 'Katalog Jasa',
                    'path' => '/settings/services',
                    'icon' => 'PackageOpen',
                    'permission_name' => 'manage services',
                    'module_id' => $settingsModule->id,
                    'order_priority' => 50,
                ]
            );
        }

        // 5. Auto-assign these menus to Superadmin & Owner roles
        $roles = Role::whereIn('name', ['superadmin', 'owner'])->get();
        $allServiceMenuIds = Menu::whereIn('route_name', [
            'service-orders.create',
            'service-orders.board',
            'service-orders.index',
            'settings.services.index'
        ])->pluck('id')->toArray();

        foreach ($roles as $role) {
            $currentMenus = \DB::table('menu_role')
                ->where('role_id', $role->id)
                ->pluck('menu_id')
                ->toArray();
            
            $newMenus = array_unique(array_merge($currentMenus, $allServiceMenuIds));
            $role->menus()->sync($newMenus);
        }

        // 6. Clear Menu Caches
        app(RoleService::class)->clearAllMenuCaches();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::whereIn('route_name', [
            'service-orders.create',
            'service-orders.board',
            'service-orders.index',
            'settings.services.index'
        ])->delete();
    }
};
