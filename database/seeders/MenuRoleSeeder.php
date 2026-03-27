<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MenuRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = Role::where('name', 'superadmin')->first();
        
        if ($superAdmin) {
            // Attach all menus to superadmin
            $menuIds = Menu::pluck('id')->toArray();
            $superAdmin->menus()->sync($menuIds);
        }

        $cashier = Role::where('name', 'cashier')->first();
        if ($cashier) {
            // Cashier usually only sees Dashboard and POS
            $cashierMenus = Menu::whereIn('route_name', ['dashboard', 'pos.index', 'produk.index'])->pluck('id')->toArray();
            $cashier->menus()->sync($cashierMenus);
        }
    }
}
