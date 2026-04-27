<?php

use App\Models\Menu;
use App\Models\Module;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $module = Module::where('slug', 'crm')->first();
        if (! $module) {
            return;
        }

        $menu = Menu::updateOrCreate(
            ['route_name' => 'customer.prices.all'],
            [
                'name' => 'Manajemen Harga Customer',
                'path' => '/customer-prices',
                'icon' => 'Tag',
                'permission_name' => 'manage customers',
                'group_name' => 'Master Data',
                'module_id' => $module->id,
                'order_priority' => 520,
                'is_active' => true,
            ]
        );

        $role = Role::where('name', 'superadmin')->first();
        if ($role) {
            $role->menus()->syncWithoutDetaching([$menu->id]);
        }

        // Clear Cache
        app(RoleService::class)->clearAllMenuCaches();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::where('route_name', 'customer.prices.all')->delete();
    }
};
