<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CustomerMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu = Menu::updateOrCreate(
            ['route_name' => 'customer.index'],
            [
                'name' => 'Master Customer',
                'path' => '/customers',
                'icon' => 'User',
                'permission_name' => 'manage customers',
                'group_name' => 'CRM',
                'module_id' => 6,
                'order_priority' => 25,
            ]
        );

        // Sync to Superadmin
        $superAdmin = Role::where('name', 'superadmin')->first();
        if ($superAdmin) {
            $superAdmin->menus()->syncWithoutDetaching([$menu->id]);
        }
    }
}
