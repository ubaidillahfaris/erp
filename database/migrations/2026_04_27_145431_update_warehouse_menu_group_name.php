<?php

use App\Models\Menu;
use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Menu::whereIn('route_name', [
            'warehouses.index',
            'stock-transfers.index',
        ])->update(['group_name' => 'warehouse']);

        // Clear Cache
        app(RoleService::class)->clearAllMenuCaches();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::whereIn('route_name', [
            'warehouses.index',
            'stock-transfers.index',
        ])->update(['group_name' => 'Inventory Control']);
    }
};
