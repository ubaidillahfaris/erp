<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class KopiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create or get 'Kilogram' unit
        $satuanKg = \App\Models\Satuan::firstOrCreate(
            ['simbol' => 'kg'],
            ['nama' => 'Kilogram', 'deskripsi' => 'Satuan berat (Kilogram)']
        );

        // 2. Create or get 'Kopi Bubuk' raw material
        $kopi = \App\Models\Produk::firstOrCreate(
            ['sku' => 'RAW-KOPI-01'],
            [
                'nama' => 'Kopi Bubuk House Blend',
                'kategori' => 'Bahan Baku',
                'stok_minimal' => 2,
                'satuan_id' => $satuanKg->id,
                'type' => 'raw_material',
                'deskripsi' => 'Bahan baku utama untuk minuman kopi',
                'is_active' => true,
            ]
        );

        // 3. Create Restock record (Include Ongkir)
        $hargaKopi = 174000;
        $ongkir = 15000;
        $totalBiaya = $hargaKopi + $ongkir;

        $restock = \App\Models\Restock::create([
            'tanggal' => now(),
            'keterangan' => 'Restock Kopi 1kg + Ongkir 15rb',
            'total_biaya' => $totalBiaya,
        ]);

        // 4. Create Restock Item
        \App\Models\RestockItem::create([
            'restock_id' => $restock->id,
            'produk_id' => $kopi->id,
            'jumlah' => 1,
            'harga_satuan' => $hargaKopi,
        ]);

        $this->command->info('Berhasil membuat data seeder Kopi 1kg (174rb + Ongkir 15rb)');
    }
}
