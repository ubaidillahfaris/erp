<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Module;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Database\Seeder;

class AccountingMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find Finance module (slug: 'finance')
        $financeModule = Module::where('slug', 'finance')->firstOrFail();

        // Deactivate legacy menus (IDs from user request)
        Menu::whereIn('id', [43, 44])->update(['is_active' => false]);

        // Create 3 new accounting menus
        $menus = [
            [
                'name' => 'Chart of Accounts',
                'path' => '/accounting/accounts',
                'icon' => 'Landmark',
                'permission_name' => 'view reports',
                'module_id' => $financeModule->id,
                'is_active' => true,
                'order_priority' => 21,
            ],
            [
                'name' => 'Buku Jurnal',
                'path' => '/accounting/journal',
                'icon' => 'FileText',
                'permission_name' => 'view reports',
                'module_id' => $financeModule->id,
                'is_active' => true,
                'order_priority' => 22,
            ],
            [
                'name' => 'Trial Balance',
                'path' => '/accounting/trial-balance',
                'icon' => 'PieChart',
                'permission_name' => 'view reports',
                'module_id' => $financeModule->id,
                'is_active' => true,
                'order_priority' => 23,
            ],
        ];

        $createdMenuIds = [];
        foreach ($menus as $menuData) {
            $menu = Menu::firstOrCreate(
                ['path' => $menuData['path']],
                $menuData
            );
            $createdMenuIds[] = $menu->id;
        }

        // Assign to all roles that have 'view reports' permission
        $roles = Role::permission('view reports')->get();
        foreach ($roles as $role) {
            $role->menus()->syncWithoutDetaching($createdMenuIds);
        }

        // Invalidate all menu caches
        app(RoleService::class)->clearAllMenuCaches();
    }
}
