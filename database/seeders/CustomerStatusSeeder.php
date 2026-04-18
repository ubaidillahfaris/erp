<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = ['Active', 'Suspended', 'Blacklisted'];

        foreach ($statuses as $status) {
            \App\Models\CustomerStatus::updateOrCreate(['name' => $status]);
        }
    }
}
