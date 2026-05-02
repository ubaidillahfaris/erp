<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SalesMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure Transaksi module exists (safety)
        $module = Module::updateOrCreate(
            ['slug' => 'transaksi'],
            ['name' => 'Transaksi', 'order_priority' => 5, 'is_active' => true]
        );

        $menu = Menu::updateOrCreate(
            ['route_name' => 'sales.index'],
            [
                'name' => 'Riwayat Penjualan',
                'path' => '/sales',
                'icon' => 'History',
                'permission_name' => 'void sales',
                'group_name' => 'Transaksi',
                'module_id' => $module->id,
                'order_priority' => 24,
                'is_active' => true,
            ]
        );

        // Map roles to NEW Module (important for sidebar visibility)
        $rolesToSync = Role::whereIn('name', ['superadmin', 'cashier'])->get();
        foreach ($rolesToSync as $role) {
            $role->modules()->syncWithoutDetaching([$module->id]);
            $role->menus()->syncWithoutDetaching([$menu->id]);
        }
    }
}
