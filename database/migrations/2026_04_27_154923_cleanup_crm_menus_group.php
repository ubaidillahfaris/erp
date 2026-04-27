<?php

use App\Models\Menu;
use App\Models\Module;
use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $module = Module::where('slug', 'crm')->first();
        if (! $module) {
            return;
        }

        // Move all menus in CRM module to have NULL group_name 
        // so they default to the Module name "CRM" in the sidebar
        Menu::where('module_id', $module->id)->update([
            'group_name' => null
        ]);

        // Explicitly ensure the routes we care about are active and correctly ordered
        Menu::where('route_name', 'customer.index')->update(['order_priority' => 510]);
        Menu::where('route_name', 'customer.prices.all')->update(['order_priority' => 520]);

        // Clear Cache
        app(RoleService::class)->clearAllMenuCaches();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy way to reverse group_name to "Master Data" without knowing what they were
        // but we can put Master Customer back if needed.
        Menu::where('route_name', 'customer.index')->update(['group_name' => 'Master Data']);
    }
};
