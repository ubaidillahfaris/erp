<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Satuan;
use App\Models\SatuanConversion;

class SatuanConversionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure core standard units exist
        $units = [
            'kg' => ['nama' => 'Kilogram', 'deskripsi' => 'Satuan berat standar'],
            'gr' => ['nama' => 'Gram', 'deskripsi' => 'Satuan berat kecil'],
            'L' => ['nama' => 'Liter', 'deskripsi' => 'Satuan volume standar'],
            'mil' => ['nama' => 'Mililiter', 'deskripsi' => 'Satuan volume kecil'],
            'mil-air' => ['nama' => 'Mililiter Air', 'deskripsi' => 'Satuan volume untuk air/cairan encer'],
            'pcs' => ['nama' => 'Pcs', 'deskripsi' => 'Satuan per biji'],
            'can' => ['nama' => 'Can/Kaleng', 'deskripsi' => 'Satuan kaleng standar'],
            'sdt-g' => ['nama' => 'Sdt Gula', 'deskripsi' => 'Satuan sendok teh gula'],
            'sdt-kp' => ['nama' => 'Sdt Kopi', 'deskripsi' => 'Satuan sendok teh kopi'],
            'gel' => ['nama' => 'Gelas', 'deskripsi' => 'Satuan gelas standar (250ml)'],
            'cang' => ['nama' => 'Cangkir', 'deskripsi' => 'Satuan cangkir standar (240ml)'],
        ];

        $unitIds = [];
        foreach ($units as $simbol => $data) {
            $satuan = Satuan::firstOrCreate(
                ['simbol' => $simbol],
                ['nama' => $data['nama'], 'deskripsi' => $data['deskripsi']]
            );
            $unitIds[$simbol] = $satuan->id;
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
                SatuanConversion::updateOrCreate([
                    'satuan_id' => $unitIds[$conv['from']],
                    'to_satuan_id' => $unitIds[$conv['to']]
                ], [
                    'rasio' => $conv['ratio']
                ]);
            }
        }

        $this->command->info('Standard generic units and conversions seeded successfully.');
    }
}
