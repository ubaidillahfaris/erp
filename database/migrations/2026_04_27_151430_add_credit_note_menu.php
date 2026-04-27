<?php

use App\Models\Menu;
use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $module = Module::where('slug', 'transaksi')->first();
        if (! $module) {
            return;
        }

        $menu = Menu::updateOrCreate(
            ['route_name' => 'credit-notes.index'],
            [
                'name' => 'Nota Kredit & Retur',
                'path' => '/credit-notes',
                'icon' => 'RotateCcw',
                'permission_name' => 'void sales',
                'group_name' => 'Transaksi',
                'module_id' => $module->id,
                'order_priority' => 25,
                'is_active' => true,
            ]
        );

        $roles = Role::whereIn('name', ['superadmin', 'cashier'])->get();
        foreach ($roles as $role) {
            $role->menus()->syncWithoutDetaching([$menu->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::where('route_name', 'credit-notes.index')->delete();
    }
};
