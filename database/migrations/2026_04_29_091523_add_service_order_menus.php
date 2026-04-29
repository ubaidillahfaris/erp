<?php

use App\Models\Menu;
use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $transaksiModule = Module::where('slug', 'transaksi')->first();

        if ($transaksiModule) {
            // Add Order Laundry (Generic POS)
            Menu::updateOrCreate(
                ['route_name' => 'service-orders.create'],
                [
                    'name' => 'Order Laundry',
                    'path' => '/service-orders/create',
                    'icon' => 'ShoppingCart',
                    'permission_name' => 'make sales',
                    'module_id' => $transaksiModule->id,
                    'order_priority' => 21,
                ]
            );

            // Add Board Order
            Menu::updateOrCreate(
                ['route_name' => 'service-orders.board'],
                [
                    'name' => 'Board Order',
                    'path' => '/service-orders/board',
                    'icon' => 'ClipboardList',
                    'permission_name' => 'make sales',
                    'module_id' => $transaksiModule->id,
                    'order_priority' => 22,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::whereIn('route_name', [
            'service-orders.create',
            'service-orders.board',
        ])->delete();
    }
};
