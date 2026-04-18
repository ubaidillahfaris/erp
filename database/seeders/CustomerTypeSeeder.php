<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = ['Regular', 'Wholesaler', 'Drop-shipper'];

        foreach ($types as $type) {
            \App\Models\CustomerType::updateOrCreate(['name' => $type]);
        }
    }
}
