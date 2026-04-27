<?php

use App\Models\Menu;
use App\Models\Module;
use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function run(): void
    {
        // 1. Create Permissions
        $permissions = [
            'manage accounting periods',
            'view audit logs',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // 2. Assign to superadmin
        $role = Role::where('name', 'superadmin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }

        // 3. Find Modules
        $financeModule = Module::where('slug', 'finance')->first();
        $settingsModule = Module::where('slug', 'settings')->first();

        // 4. Create Menu Entries
        $menus = [
            [
                'route_name' => 'accounting.periods.index',
                'name' => 'Accounting Periods',
                'path' => '/accounting/periods',
                'icon' => 'Landmark',
                'permission_name' => 'manage accounting periods',
                'group_name' => 'Accounting',
                'order_priority' => 50,
                'module_id' => $financeModule?->id,
            ],
            [
                'route_name' => 'system.audit-log.index',
                'name' => 'System Audit Log',
                'path' => '/system/audit-log',
                'icon' => 'HistoryIcon',
                'permission_name' => 'view audit logs',
                'group_name' => 'Administration',
                'order_priority' => 90,
                'module_id' => $settingsModule?->id,
            ],
        ];

        $menuIds = [];
        foreach ($menus as $m) {
            $menu = Menu::updateOrCreate(
                ['route_name' => $m['route_name']],
                $m
            );
            $menuIds[] = $menu->id;
        }

        // 5. Link menus to superadmin role
        if ($role && method_exists($role, 'menus')) {
            $role->menus()->syncWithoutDetaching($menuIds);
        }

        // 6. Clear Cache
        app(RoleService::class)->clearAllMenuCaches();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::whereIn('route_name', [
            'accounting.periods.index',
            'system.audit-log.index',
        ])->delete();
    }
};
