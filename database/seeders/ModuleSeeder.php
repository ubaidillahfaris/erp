<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            ['name' => 'Platform', 'slug' => 'platform', 'order_priority' => 1],
            ['name' => 'Inventory', 'slug' => 'inventory', 'order_priority' => 2],
            ['name' => 'Production', 'slug' => 'production', 'order_priority' => 3],
            ['name' => 'Purchasing', 'slug' => 'purchasing', 'order_priority' => 4],
            ['name' => 'Finance', 'slug' => 'finance', 'order_priority' => 5],
            ['name' => 'CRM', 'slug' => 'crm', 'order_priority' => 6],
            ['name' => 'HR', 'slug' => 'hr', 'order_priority' => 7],
            ['name' => 'Report', 'slug' => 'report', 'order_priority' => 8],
            ['name' => 'Settings', 'slug' => 'settings', 'order_priority' => 9],
            ['name' => 'Transaksi', 'slug' => 'transaksi', 'order_priority' => 5],
            ['name' => 'Assets', 'slug' => 'assets', 'order_priority' => 6],
        ];

        foreach ($modules as $module) {
            try {
                Log::info('Seeding module: '.$module['slug']);
                Module::updateOrCreate(['slug' => $module['slug']], $module);
            } catch (\Exception $e) {
                Log::error('Failed to seed module: '.$module['slug'].' - '.$e->getMessage());
                throw $e;
            }
        }
    }
}
