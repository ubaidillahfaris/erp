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
        // Finance module (slug: 'finance')
        $financeModule = Module::where('slug', 'finance')->firstOrFail();

        // Account / Module mapping is now centralized in MenuSeeder for structure.
        // This seeder ensures permissions and role assignments are correct.

        $accountingMenus = [
            '/accounting/accounts',
            '/accounting/journal',
            '/accounting/trial-balance',
            '/accounting/aging',
            '/pengeluaran'
        ];

        $menuIds = Menu::whereIn('path', $accountingMenus)->pluck('id')->toArray();

        // Assign to all roles that have 'view reports' permission
        $roles = Role::permission('view reports')->get();
        foreach ($roles as $role) {
            $role->menus()->syncWithoutDetaching($menuIds);
        }

        // Invalidate all menu caches
        app(RoleService::class)->clearAllMenuCaches();
    }
}
