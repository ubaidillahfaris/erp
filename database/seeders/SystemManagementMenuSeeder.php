<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class SystemManagementMenuSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure System/Platform Module exists
        $module = Module::updateOrCreate(
            ['slug' => 'platform'],
            [
                'name' => 'Platform Management',
                'version' => '1.0.0',
                'icon' => 'settings',
                'order_priority' => 99,
                'is_active' => true,
            ]
        );

        // 2. Create Parent Menu: System Management
        $parentMenu = Menu::updateOrCreate(
            ['route_name' => 'admin.system.index'],
            [
                'module_id' => $module->id,
                'name' => 'System Management',
                'path' => '/admin/system',
                'icon' => 'shield-check',
                'permission_name' => 'system.manage',
                'feature_key' => 'system.core',
                'group_name' => 'System',
                'order_priority' => 100,
                'is_active' => true,
            ]
        );

        // 3. Child Menu: Module Registry (Superadmin)
        Menu::updateOrCreate(
            ['route_name' => 'admin.modules.index'],
            [
                'module_id' => $module->id,
                'parent_id' => $parentMenu->id,
                'name' => 'Module Registry',
                'path' => '/admin/system/modules',
                'icon' => 'package',
                'permission_name' => 'system.modules.view',
                'feature_key' => 'system.registry',
                'order_priority' => 1,
                'is_active' => true,
            ]
        );

        // 4. Child Menu: Tenant & Add-ons (Superadmin/Admin)
        Menu::updateOrCreate(
            ['route_name' => 'admin.tenants.index'],
            [
                'module_id' => $module->id,
                'parent_id' => $parentMenu->id,
                'name' => 'Tenant Manager',
                'path' => '/admin/system/tenants',
                'icon' => 'building-2',
                'permission_name' => 'system.tenants.view',
                'feature_key' => 'system.tenants',
                'order_priority' => 2,
                'is_active' => true,
            ]
        );
    }
}
