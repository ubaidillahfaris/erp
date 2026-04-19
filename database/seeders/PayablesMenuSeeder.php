<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PayablesMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure Finance module exists (safety)
        $module = Module::updateOrCreate(
            ['id' => 5],
            [
                'name' => 'Finance',
                'slug' => 'finance',
                'order_priority' => 5,
                'is_active' => true
            ]
        );

        $menu = Menu::updateOrCreate(
            ['route_name' => 'payables.index'],
            [
                'name' => 'Hutang & Piutang',
                'path' => '/payables',
                'icon' => 'Landmark',
                'permission_name' => 'view payables',
                'group_name' => 'Finance',
                'module_id' => $module->id,
                'order_priority' => 24,
                'is_active' => true,
            ]
        );

        // Map roles to Module and Menu (important for sidebar visibility)
        // Adjust roles as needed, assuming superadmin and manager should see this
        $rolesToSync = Role::whereIn('name', ['superadmin', 'manager', 'owner', 'finance'])->get();
        foreach ($rolesToSync as $role) {
            $role->modules()->syncWithoutDetaching([$module->id]);
            $role->menus()->syncWithoutDetaching([$menu->id]);
        }
    }
}
