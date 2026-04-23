<?php

namespace Database\Seeders;

use App\Models\CustomerType;
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
            CustomerType::updateOrCreate(['name' => $type]);
        }
    }
}
