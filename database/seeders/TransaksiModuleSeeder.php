<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Seeder;

class TransaksiModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Transaksi module exists
        $transaksiModule = Module::updateOrCreate(
            ['id' => 10],
            [
                'name' => 'Transaksi',
                'slug' => 'transaksi',
                'icon' => 'ReceiptText',
                'order_priority' => 5,
                'is_active' => true,
            ]
        );

        // 2. Map operational transaction menus to this module
        $transaksiMenus = [
            'sales.index',
            'purchasing.index',
            'restock.index',
            'pengeluaran.index',
        ];

        Menu::whereIn('route_name', $transaksiMenus)->update(['module_id' => $transaksiModule->id]);

        // 3. Ensure 'Laba Rugi' and 'Jurnal Umum' stay in Finance (Module 5)
        Menu::whereIn('route_name', ['profit-loss.index', 'journal.index'])
            ->update(['module_id' => 5]);

        // 4. Link Transaksi module to roles for visibility
        $rolesToSync = Role::whereIn('name', ['superadmin', 'cashier'])->get();
        foreach ($rolesToSync as $role) {
            $role->modules()->syncWithoutDetaching([$transaksiModule->id]);
        }
    }
}
