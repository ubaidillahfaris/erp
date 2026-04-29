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
        $rolesWithFullAccess = Role::whereIn('name', ['superadmin', 'owner'])->get();
        $menuIds = Menu::pluck('id')->toArray();

        foreach ($rolesWithFullAccess as $role) {
            $role->menus()->sync($menuIds);
        }

        $cashier = Role::where('name', 'cashier')->first();
        if ($cashier) {
            // Cashier usually only sees Dashboard and POS
            $cashierMenus = Menu::whereIn('route_name', ['dashboard', 'pos.index', 'product.index'])->pluck('id')->toArray();
            $cashier->menus()->sync($cashierMenus);
        }
    }
}
