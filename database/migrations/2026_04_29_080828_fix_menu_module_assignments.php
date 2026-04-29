<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix incorrect menu → module assignments that were set by earlier seeders.
     *
     * Rules (module IDs from ModuleSeeder):
     *   1  = platform    → Dashboard only
     *   4  = purchasing  → Purchasing, Restock, Vendor
     *   5  = finance     → All accounting + Pengeluaran
     *  10  = transaksi   → POS + Sales history
     */
    public function up(): void
    {
        // Transaksi module: POS + Sales history only
        DB::table('menus')
            ->whereIn('route_name', ['pos.index', 'sales.index'])
            ->update(['module_id' => 10]);

        // Finance module: pengeluaran belongs here, not transaksi
        DB::table('menus')
            ->whereIn('route_name', [
                'pengeluaran.index',
                'profit-loss.index',
                'journal.index',
            ])
            ->update(['module_id' => 5]);

        // Purchasing module: purchasing, restock, vendor stay here
        DB::table('menus')
            ->whereIn('route_name', [
                'purchasing.index',
                'restock.index',
                'vendor.index',
            ])
            ->update(['module_id' => 4]);
    }

    public function down(): void
    {
        // Revert: move pos back to platform, pengeluaran back to transaksi
        DB::table('menus')
            ->whereIn('route_name', ['pos.index'])
            ->update(['module_id' => 1]);

        DB::table('menus')
            ->whereIn('route_name', [
                'pengeluaran.index',
                'purchasing.index',
                'restock.index',
                'vendor.index',
            ])
            ->update(['module_id' => 10]);
    }
};
