<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Module;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class NewFeaturesMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Permissions Exist
        $permissions = [
            'manage accounting periods',
            'view audit logs',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // 2. Assign to superadmin role
        $role = Role::where('name', 'superadmin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }

        // 3. Find Modules
        $financeModule = Module::where('slug', 'finance')->first();
        $settingsModule = Module::where('slug', 'settings')->first();

        // 4. Create/Update Menu Entries
        $menus = [
            [
                'route_name' => 'accounting.periods.index',
                'name' => 'Accounting Periods',
                'path' => '/accounting/periods',
                'icon' => 'Landmark',
                'permission_name' => 'manage accounting periods',
                'group_name' => 'Accounting',
                'order_priority' => 460, // After operasional
                'module_id' => $financeModule?->id,
            ],
            [
                'route_name' => 'system.audit-log.index',
                'name' => 'System Audit Log',
                'path' => '/system/audit-log',
                'icon' => 'HistoryIcon',
                'permission_name' => 'view audit logs',
                'group_name' => 'Administration',
                'order_priority' => 940, // After roles
                'module_id' => $settingsModule?->id,
            ],
        ];

        $menuIds = [];
        foreach ($menus as $m) {
            $menu = Menu::updateOrCreate(
                ['route_name' => $m['route_name']],
                array_merge($m, ['is_active' => true])
            );
            $menuIds[] = $menu->id;
        }

        // 5. Link menus to superadmin role pivot
        if ($role) {
            $role->menus()->syncWithoutDetaching($menuIds);
        }

        // 6. Clear Cache
        app(RoleService::class)->clearAllMenuCaches();
    }
}
