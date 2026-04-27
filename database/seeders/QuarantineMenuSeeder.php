<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Module;
use Illuminate\Database\Seeder;

class QuarantineMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stockModule = Module::where('slug', 'stock')->first();

        if ($stockModule) {
            Menu::updateOrCreate(
                ['path' => '/quarantine'],
                [
                    'module_id' => $stockModule->id,
                    'name' => 'Karantina Retur',
                    'icon' => 'RotateCcw',
                    'order_priority' => 50,
                    'is_active' => true,
                    'group_name' => 'Stock',
                ]
            );
        }
    }
}
