<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NasabahStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = ['Active', 'Locked', 'Closed'];

        foreach ($statuses as $status) {
            \App\Models\NasabahStatus::updateOrCreate(['name' => $status]);
        }
    }
}
