<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restock;
use App\Models\RestockItem;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class KopiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create or get 'Kilogram' unit
        $unitKg = Unit::firstOrCreate(
            ['symbol' => 'kg'],
            ['name' => 'Kilogram', 'description' => 'Unit berat (Kilogram)']
        );

        $catBahanBaku = Category::firstOrCreate(['name' => 'Raw Materials'], ['slug' => 'bahan-baku']);

        // 2. Create or get 'Kopi Bubuk' raw material
        $kopi = Product::firstOrCreate(
            ['sku' => 'RAW-KOPI-01'],
            [
                'name' => 'Kopi Bubuk House Blend',
                'category_id' => $catBahanBaku->id,
                'min_stock' => 2,
                'unit_id' => $unitKg->id,
                'type' => 'raw_material',
                'description' => 'Bahan baku utama untuk minuman kopi',
                'is_active' => true,
            ]
        );

        // 3. Create Restock record (Include Ongkir)
        $priceKopi = 174000;
        $ongkir = 15000;
        $totalBiaya = $priceKopi + $ongkir;

        $restock = Restock::create([
            'date' => now(),
            'notes' => 'Restock Kopi 1kg + Ongkir 15rb',
            'total_biaya' => $totalBiaya,
        ]);

        // 4. Create Restock Item
        RestockItem::create([
            'restock_id' => $restock->id,
            'product_id' => $kopi->id,
            'quantity' => 1,
            'unit_price' => $priceKopi,
        ]);

        $this->command->info('Berhasil membuat data seeder Kopi 1kg (174rb + Ongkir 15rb)');
    }
}
