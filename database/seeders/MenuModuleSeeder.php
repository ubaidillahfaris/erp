<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mappings = [
            1 => [32, 33],
            2 => [34, 35, 36, 40],
            3 => [37, 39],
            4 => [41, 42],
            5 => [43, 44, 45],
            6 => [38],
            9 => [46, 47],
        ];

        foreach ($mappings as $moduleId => $menuIds) {
            \App\Models\Menu::whereIn('id', $menuIds)->update(['module_id' => $moduleId]);
        }
    }
}
