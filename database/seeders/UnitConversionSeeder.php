<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Database\Seeder;

class UnitConversionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure core standard units exist
        $units = [
            'kg' => ['name' => 'Kilogram', 'description' => 'Unit berat standar'],
            'gr' => ['name' => 'Gram', 'description' => 'Unit berat kecil'],
            'L' => ['name' => 'Liter', 'description' => 'Unit volume standar'],
            'mil' => ['name' => 'Mililiter', 'description' => 'Unit volume kecil'],
            'mil-air' => ['name' => 'Mililiter Air', 'description' => 'Unit volume untuk air/cairan encer'],
            'pcs' => ['name' => 'Pcs', 'description' => 'Unit per biji'],
            'can' => ['name' => 'Can/Kaleng', 'description' => 'Unit kaleng standar'],
            'sdt-g' => ['name' => 'Sdt Gula', 'description' => 'Unit sendok teh gula'],
            'sdt-kp' => ['name' => 'Sdt Kopi', 'description' => 'Unit sendok teh kopi'],
            'gel' => ['name' => 'Gelas', 'description' => 'Unit gelas standar (250ml)'],
            'cang' => ['name' => 'Cangkir', 'description' => 'Unit cangkir standar (240ml)'],
        ];

        $unitIds = [];
        foreach ($units as $symbol => $data) {
            $unit = Unit::firstOrCreate(
                ['symbol' => $symbol],
                ['name' => $data['name'], 'description' => $data['description']]
            );
            $unitIds[$symbol] = $unit->id;
        }

        // 2. Define Conversions
        $conversions = [
            // Weight
            ['from' => 'kg', 'to' => 'gr', 'ratio' => 1000],

            // Volume
            ['from' => 'L', 'to' => 'mil', 'ratio' => 1000],

            // Cross mass-volume approximations
            ['from' => 'mil', 'to' => 'gr', 'ratio' => 1], // Standard liquid density
            ['from' => 'mil-air', 'to' => 'gr', 'ratio' => 1],

            // Packing / Groceries
            ['from' => 'can', 'to' => 'gr', 'ratio' => 370], // Standard SKM can

            // Culinary Measures
            ['from' => 'sdt-g', 'to' => 'gr', 'ratio' => 5], // 1 sdt gula = ~5 gr
            ['from' => 'sdt-kp', 'to' => 'gr', 'ratio' => 2], // 1 sdt kopi = ~2 gr
            ['from' => 'gel', 'to' => 'mil', 'ratio' => 250], // 1 gelas = ~250 ml
            ['from' => 'cang', 'to' => 'mil', 'ratio' => 240], // 1 cangkir = ~240 ml
        ];

        // 3. Insert Conversions
        foreach ($conversions as $conv) {
            if (isset($unitIds[$conv['from']]) && isset($unitIds[$conv['to']])) {
                UnitConversion::updateOrCreate([
                    'unit_id' => $unitIds[$conv['from']],
                    'target_unit_id' => $unitIds[$conv['to']],
                ], [
                    'ratio' => $conv['ratio'],
                ]);
            }
        }

        $this->command->info('Standard generic units and conversions seeded successfully.');
    }
}
