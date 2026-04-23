<?php

namespace Database\Seeders;

use App\Models\CustomerStatus;
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
            CustomerStatus::updateOrCreate(['name' => $status]);
        }
    }
}
