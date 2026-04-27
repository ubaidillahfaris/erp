<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Pengeluaran;
use App\Models\Price;
use App\Models\Product;
use App\Models\Restock;
use App\Models\RestockItem;
use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Database\Seeder;

class WarkopSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure units exist
        $pcs = Unit::firstOrCreate(['symbol' => 'pcs'], ['name' => 'Pcs', 'description' => 'Unit per biji']);
        $liter = Unit::firstOrCreate(['symbol' => 'L'], ['name' => 'Liter', 'description' => 'Unit volume Liter']);
        $can = Unit::firstOrCreate(['symbol' => 'can'], ['name' => 'Can/Kaleng', 'description' => 'Unit kaleng']);
        $pack = Unit::firstOrCreate(['symbol' => 'pack'], ['name' => 'Pack', 'description' => 'Unit pack']);
        $slop = Unit::firstOrCreate(['symbol' => 'slp'], ['name' => 'Slop', 'description' => 'Unit slop (25 pcs)']);
        $sachet = Unit::firstOrCreate(['symbol' => 'sch'], ['name' => 'Sachet', 'description' => 'Unit sachet']);
        $renteng = Unit::firstOrCreate(['symbol' => 'rtg'], ['name' => 'Renteng', 'description' => 'Unit renteng (10 sachet)']);
        $box = Unit::firstOrCreate(['symbol' => 'box'], ['name' => 'Box/Dus', 'description' => 'Unit box/dus']);

        // 2. Unit Conversions
        UnitConversion::updateOrCreate(
            ['unit_id' => $slop->id, 'target_unit_id' => $pcs->id],
            ['ratio' => 25]
        );
        UnitConversion::updateOrCreate(
            ['unit_id' => $renteng->id, 'target_unit_id' => $sachet->id],
            ['ratio' => 10]
        );
        UnitConversion::updateOrCreate(
            ['unit_id' => $box->id, 'target_unit_id' => $pcs->id],
            ['ratio' => 40]
        );

        // 3. Categories
        $catPackaging = Category::firstOrCreate(['name' => 'Packaging'], ['slug' => 'packaging']);
        $catSusu = Category::firstOrCreate(['name' => 'Susu'], ['slug' => 'susu']);
        $catSachet = Category::firstOrCreate(['name' => 'Sachet'], ['slug' => 'sachet']);
        $catMieInstan = Category::firstOrCreate(['name' => 'Mie Instan'], ['slug' => 'mie-instan']);
        $catMinuman = Category::firstOrCreate(['name' => 'Minuman'], ['slug' => 'minuman']);

        // 4. Packaging items
        $gelas = Product::firstOrCreate(['sku' => 'PKG-GELAS-01'], [
            'name' => 'Gelas Kaca Warkop',
            'category_id' => $catPackaging->id,
            'min_stock' => 12,
            'unit_id' => $pcs->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['product_id' => $gelas->id, 'is_current' => true], [
            'unit_id' => $pcs->id,
            'purchase_price' => 5000,
            'retail_price' => 0,
        ]);

        $cup = Product::firstOrCreate(['sku' => 'PKG-CUP-01'], [
            'name' => 'Cup Plastik Es Kopi',
            'category_id' => $catPackaging->id,
            'min_stock' => 50,
            'unit_id' => $pcs->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['product_id' => $cup->id, 'is_current' => true], [
            'unit_id' => $pcs->id,
            'purchase_price' => 23000 / 25,
            'retail_price' => 0,
        ]);

        // 4. Dairy items
        $susuDiamond = Product::firstOrCreate(['sku' => 'RAW-SUSU-01'], [
            'name' => 'Susu Diamond Fresh Milk',
            'category_id' => $catSusu->id,
            'min_stock' => 5,
            'unit_id' => $liter->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['product_id' => $susuDiamond->id, 'is_current' => true], [
            'unit_id' => $liter->id,
            'purchase_price' => 23000,
            'retail_price' => 0,
        ]);

        $skmCarnation = Product::firstOrCreate(['sku' => 'RAW-SKM-01'], [
            'name' => 'SKM Carnation',
            'category_id' => $catSusu->id,
            'min_stock' => 6,
            'unit_id' => $can->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['product_id' => $skmCarnation->id, 'is_current' => true], [
            'unit_id' => $can->id,
            'purchase_price' => 15000,
            'retail_price' => 0,
        ]);

        $skmTigaSapi = Product::firstOrCreate(['sku' => 'RAW-SKM-02'], [
            'name' => 'SKM Tiga Sapi',
            'category_id' => $catSusu->id,
            'min_stock' => 6,
            'unit_id' => $can->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['product_id' => $skmTigaSapi->id, 'is_current' => true], [
            'unit_id' => $can->id,
            'purchase_price' => 11500,
            'retail_price' => 0,
        ]);

        // 5. Instant Food & Drinks
        $sachetItems = [
            'RAW-SCH-01' => 'Nutrisari Jeruk Peras',
            'RAW-SCH-02' => 'Milo Sachet',
            'RAW-SCH-03' => 'Extra Joss Original',
            'RAW-SCH-04' => 'Kopi Kapal Api Mix',
            'RAW-SCH-05' => 'Good Day Freeze Mocafrio',
            'RAW-SCH-06' => 'Beng Beng Drink',
            'RAW-SCH-07' => 'Chocolatos Drink',
            'RAW-SCH-08' => 'Kuku Bima Ener-G Anggur',
            'RAW-SCH-09' => 'Teh Tarik Max Tea',
            'RAW-SCH-10' => 'Torabika Cappuccino',
        ];

        foreach ($sachetItems as $sku => $name) {
            $p = Product::firstOrCreate(['sku' => $sku], [
                'name' => $name,
                'category_id' => $catSachet->id,
                'min_stock' => 20,
                'unit_id' => $sachet->id,
                'type' => 'raw_material',
                'is_active' => true,
            ]);
            Price::updateOrCreate(['product_id' => $p->id, 'is_current' => true], [
                'unit_id' => $sachet->id,
                'purchase_price' => 23000 / 10,
                'retail_price' => 0,
            ]);
        }

        $mieGoreng = Product::firstOrCreate(['sku' => 'RAW-MIE-01'], [
            'name' => 'Indomie Goreng',
            'category_id' => $catMieInstan->id,
            'min_stock' => 40,
            'unit_id' => $pcs->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['product_id' => $mieGoreng->id, 'is_current' => true], [
            'unit_id' => $pcs->id,
            'purchase_price' => 114000 / 40,
            'retail_price' => 0,
        ]);

        $mieSoto = Product::firstOrCreate(['sku' => 'RAW-MIE-02'], [
            'name' => 'Indomie Soto Spesial',
            'category_id' => $catMieInstan->id,
            'min_stock' => 40,
            'unit_id' => $pcs->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['product_id' => $mieSoto->id, 'is_current' => true], [
            'unit_id' => $pcs->id,
            'purchase_price' => 114000 / 40,
            'retail_price' => 0,
        ]);

        $aqua = Product::firstOrCreate(['sku' => 'RAW-AQUA-01'], [
            'name' => 'Aqua Tanggung 600ml',
            'category_id' => $catMinuman->id,
            'min_stock' => 2,
            'unit_id' => $box->id,
            'type' => 'raw_material',
            'is_active' => true,
            'description' => '1 Box isi 24 botol',
        ]);
        Price::updateOrCreate(['product_id' => $aqua->id, 'is_current' => true], [
            'unit_id' => $box->id,
            'purchase_price' => 48000,
            'retail_price' => 0,
        ]);

        // 6. Create initial Restocks to set HPP
        if (! Restock::where('notes', 'Initial Stock Setup for Warkop')->exists()) {
            $restockItems = [
                ['product_id' => $cup->id, 'quantity' => 1, 'unit_price' => 23000, 'unit_id' => $slop->id],
                ['product_id' => $susuDiamond->id, 'quantity' => 10, 'unit_price' => 23000, 'unit_id' => $liter->id],
                ['product_id' => $skmCarnation->id, 'quantity' => 12, 'unit_price' => 15000, 'unit_id' => $can->id],
                ['product_id' => $skmTigaSapi->id, 'quantity' => 12, 'unit_price' => 11500, 'unit_id' => $can->id],
                ['product_id' => $mieGoreng->id, 'quantity' => 1, 'unit_price' => 114000, 'unit_id' => $box->id],
                ['product_id' => $mieSoto->id, 'quantity' => 1, 'unit_price' => 114000, 'unit_id' => $box->id],
                ['product_id' => $aqua->id, 'quantity' => 2, 'unit_price' => 48000, 'unit_id' => $box->id],
            ];

            foreach ($sachetItems as $sku => $name) {
                $p = Product::where('sku', $sku)->first();
                $restockItems[] = ['product_id' => $p->id, 'quantity' => 1, 'unit_price' => 23000, 'unit_id' => $renteng->id];
            }

            $totalRestockCost = collect($restockItems)->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

            $restock = Restock::create([
                'date' => now(),
                'notes' => 'Initial Stock Setup for Warkop',
                'total_biaya' => $totalRestockCost,
            ]);

            foreach ($restockItems as $itemData) {
                RestockItem::create(array_merge($itemData, ['restock_id' => $restock->id]));
            }
        }

        // 7. Regular Expenses
        if (! Pengeluaran::where('nama_pengeluaran', 'Token PLN Mingguan')->whereDate('date', now()->toDateString())->exists()) {
            Pengeluaran::create([
                'jenis_pengeluaran' => 'Listrik',
                'nama_pengeluaran' => 'Token PLN Mingguan',
                'nominal' => 50000,
                'notes' => 'Token PLN Mingguan',
                'date' => now(),
            ]);
        }

        if (! Pengeluaran::where('nama_pengeluaran', 'Iuran Kebersihan')->whereDate('date', now()->toDateString())->exists()) {
            Pengeluaran::create([
                'jenis_pengeluaran' => 'Kebersihan',
                'nama_pengeluaran' => 'Iuran Kebersihan',
                'nominal' => 32000,
                'notes' => 'Iuran Kebersihan',
                'date' => now(),
            ]);
        }

        $this->command->info('Warkop seeder completed with initial restocks and prices for accurate HPP.');
    }
}
