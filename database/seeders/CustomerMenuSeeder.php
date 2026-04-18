<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class CustomerMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Menu::updateOrCreate(
            ['route_name' => 'customer.index'],
            [
                'name' => 'Master Customer',
                'path' => '/customers',
                'icon' => 'User',
                'permission_name' => 'manage customers',
                'group_name' => 'CRM',
                'module_id' => 6,
                'order_priority' => 25,
            ]
        );
    }
}
