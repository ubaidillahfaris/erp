<?php

namespace Database\Seeders;

use App\Models\NasabahStatus;
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
            NasabahStatus::updateOrCreate(['name' => $status]);
        }
    }
}
