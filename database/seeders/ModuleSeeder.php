<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            ['id' => 1, 'name' => 'Platform', 'slug' => 'platform', 'order_priority' => 1],
            ['id' => 2, 'name' => 'Inventory', 'slug' => 'inventory', 'order_priority' => 2],
            ['id' => 3, 'name' => 'Production', 'slug' => 'production', 'order_priority' => 3],
            ['id' => 4, 'name' => 'Purchasing', 'slug' => 'purchasing', 'order_priority' => 4],
            ['id' => 5, 'name' => 'Finance', 'slug' => 'finance', 'order_priority' => 5],
            ['id' => 6, 'name' => 'CRM', 'slug' => 'crm', 'order_priority' => 6],
            ['id' => 7, 'name' => 'HR', 'slug' => 'hr', 'order_priority' => 7],
            ['id' => 8, 'name' => 'Report', 'slug' => 'report', 'order_priority' => 8],
            ['id' => 9, 'name' => 'Settings', 'slug' => 'settings', 'order_priority' => 9],
            ['id' => 10, 'name' => 'Transaksi', 'slug' => 'transaksi', 'order_priority' => 5],
            ['id' => 11, 'name' => 'Assets', 'slug' => 'assets', 'order_priority' => 6],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(['id' => $module['id']], $module);
        }
    }
}
