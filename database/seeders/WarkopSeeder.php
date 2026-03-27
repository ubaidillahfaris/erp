<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\Satuan;
use App\Models\Pengeluaran;
use App\Models\Restock;
use App\Models\RestockItem;
use App\Models\SatuanConversion;
use App\Models\Price;
use Illuminate\Database\Seeder;

class WarkopSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure units exist
        $pcs = Satuan::firstOrCreate(['simbol' => 'pcs'], ['nama' => 'Pcs', 'deskripsi' => 'Satuan per biji']);
        $liter = Satuan::firstOrCreate(['simbol' => 'L'], ['nama' => 'Liter', 'deskripsi' => 'Satuan volume Liter']);
        $can = Satuan::firstOrCreate(['simbol' => 'can'], ['nama' => 'Can/Kaleng', 'deskripsi' => 'Satuan kaleng']);
        $pack = Satuan::firstOrCreate(['simbol' => 'pack'], ['nama' => 'Pack', 'deskripsi' => 'Satuan pack']);
        $slop = Satuan::firstOrCreate(['simbol' => 'slp'], ['nama' => 'Slop', 'deskripsi' => 'Satuan slop (25 pcs)']);
        $sachet = Satuan::firstOrCreate(['simbol' => 'sch'], ['nama' => 'Sachet', 'deskripsi' => 'Satuan sachet']);
        $renteng = Satuan::firstOrCreate(['simbol' => 'rtg'], ['nama' => 'Renteng', 'deskripsi' => 'Satuan renteng (10 sachet)']);
        $box = Satuan::firstOrCreate(['simbol' => 'box'], ['nama' => 'Box/Dus', 'deskripsi' => 'Satuan box/dus']);

        // 2. Unit Conversions
        SatuanConversion::updateOrCreate(
            ['satuan_id' => $slop->id, 'to_satuan_id' => $pcs->id],
            ['rasio' => 25]
        );
        SatuanConversion::updateOrCreate(
            ['satuan_id' => $renteng->id, 'to_satuan_id' => $sachet->id],
            ['rasio' => 10]
        );
        SatuanConversion::updateOrCreate(
            ['satuan_id' => $box->id, 'to_satuan_id' => $pcs->id],
            ['rasio' => 40]
        );

        // 3. Packaging items
        $gelas = Produk::firstOrCreate(['sku' => 'PKG-GELAS-01'], [
            'nama' => 'Gelas Kaca Warkop',
            'kategori' => 'Packaging',
            'stok_minimal' => 12,
            'satuan_id' => $pcs->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['produk_id' => $gelas->id, 'is_current' => true], [
            'satuan_id' => $pcs->id,
            'purchase_price' => 5000,
            'retail_price' => 0
        ]);

        $cup = Produk::firstOrCreate(['sku' => 'PKG-CUP-01'], [
            'nama' => 'Cup Plastik Es Kopi',
            'kategori' => 'Packaging',
            'stok_minimal' => 50,
            'satuan_id' => $pcs->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['produk_id' => $cup->id, 'is_current' => true], [
            'satuan_id' => $pcs->id,
            'purchase_price' => 23000 / 25,
            'retail_price' => 0
        ]);

        // 4. Dairy items
        $susuDiamond = Produk::firstOrCreate(['sku' => 'RAW-SUSU-01'], [
            'nama' => 'Susu Diamond Fresh Milk',
            'kategori' => 'Susu',
            'stok_minimal' => 5,
            'satuan_id' => $liter->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['produk_id' => $susuDiamond->id, 'is_current' => true], [
            'satuan_id' => $liter->id,
            'purchase_price' => 23000,
            'retail_price' => 0
        ]);

        $skmCarnation = Produk::firstOrCreate(['sku' => 'RAW-SKM-01'], [
            'nama' => 'SKM Carnation',
            'kategori' => 'Susu',
            'stok_minimal' => 6,
            'satuan_id' => $can->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['produk_id' => $skmCarnation->id, 'is_current' => true], [
            'satuan_id' => $can->id,
            'purchase_price' => 15000,
            'retail_price' => 0
        ]);

        $skmTigaSapi = Produk::firstOrCreate(['sku' => 'RAW-SKM-02'], [
            'nama' => 'SKM Tiga Sapi',
            'kategori' => 'Susu',
            'stok_minimal' => 6,
            'satuan_id' => $can->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['produk_id' => $skmTigaSapi->id, 'is_current' => true], [
            'satuan_id' => $can->id,
            'purchase_price' => 11500,
            'retail_price' => 0
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

        foreach ($sachetItems as $sku => $nama) {
            $p = Produk::firstOrCreate(['sku' => $sku], [
                'nama' => $nama,
                'kategori' => 'Sachet',
                'stok_minimal' => 20,
                'satuan_id' => $sachet->id,
                'type' => 'raw_material',
                'is_active' => true,
            ]);
            Price::updateOrCreate(['produk_id' => $p->id, 'is_current' => true], [
                'satuan_id' => $sachet->id,
                'purchase_price' => 23000 / 10,
                'retail_price' => 0
            ]);
        }

        $mieGoreng = Produk::firstOrCreate(['sku' => 'RAW-MIE-01'], [
            'nama' => 'Indomie Goreng',
            'kategori' => 'Mie Instan',
            'stok_minimal' => 40,
            'satuan_id' => $pcs->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['produk_id' => $mieGoreng->id, 'is_current' => true], [
            'satuan_id' => $pcs->id,
            'purchase_price' => 114000 / 40,
            'retail_price' => 0
        ]);

        $mieSoto = Produk::firstOrCreate(['sku' => 'RAW-MIE-02'], [
            'nama' => 'Indomie Soto Spesial',
            'kategori' => 'Mie Instan',
            'stok_minimal' => 40,
            'satuan_id' => $pcs->id,
            'type' => 'raw_material',
            'is_active' => true,
        ]);
        Price::updateOrCreate(['produk_id' => $mieSoto->id, 'is_current' => true], [
            'satuan_id' => $pcs->id,
            'purchase_price' => 114000 / 40,
            'retail_price' => 0
        ]);

        $aqua = Produk::firstOrCreate(['sku' => 'RAW-AQUA-01'], [
            'nama' => 'Aqua Tanggung 600ml',
            'kategori' => 'Minuman',
            'stok_minimal' => 2,
            'satuan_id' => $box->id,
            'type' => 'raw_material',
            'is_active' => true,
            'deskripsi' => '1 Box isi 24 botol',
        ]);
        Price::updateOrCreate(['produk_id' => $aqua->id, 'is_current' => true], [
            'satuan_id' => $box->id,
            'purchase_price' => 48000,
            'retail_price' => 0
        ]);

        // 6. Create initial Restocks to set HPP
        if (!Restock::where('keterangan', 'Initial Stock Setup for Warkop')->exists()) {
            $restockItems = [
                ['produk_id' => $cup->id, 'jumlah' => 1, 'harga_satuan' => 23000, 'satuan_id' => $slop->id],
                ['produk_id' => $susuDiamond->id, 'jumlah' => 10, 'harga_satuan' => 23000, 'satuan_id' => $liter->id],
                ['produk_id' => $skmCarnation->id, 'jumlah' => 12, 'harga_satuan' => 15000, 'satuan_id' => $can->id],
                ['produk_id' => $skmTigaSapi->id, 'jumlah' => 12, 'harga_satuan' => 11500, 'satuan_id' => $can->id],
                ['produk_id' => $mieGoreng->id, 'jumlah' => 1, 'harga_satuan' => 114000, 'satuan_id' => $box->id],
                ['produk_id' => $mieSoto->id, 'jumlah' => 1, 'harga_satuan' => 114000, 'satuan_id' => $box->id],
                ['produk_id' => $aqua->id, 'jumlah' => 2, 'harga_satuan' => 48000, 'satuan_id' => $box->id],
            ];

            foreach ($sachetItems as $sku => $nama) {
                $p = Produk::where('sku', $sku)->first();
                $restockItems[] = ['produk_id' => $p->id, 'jumlah' => 1, 'harga_satuan' => 23000, 'satuan_id' => $renteng->id];
            }

            $totalRestockCost = collect($restockItems)->sum(fn($i) => $i['jumlah'] * $i['harga_satuan']);

            $restock = Restock::create([
                'tanggal' => now(),
                'keterangan' => 'Initial Stock Setup for Warkop',
                'total_biaya' => $totalRestockCost,
            ]);

            foreach ($restockItems as $itemData) {
                RestockItem::create(array_merge($itemData, ['restock_id' => $restock->id]));
            }
        }

        // 7. Regular Expenses
        if (!Pengeluaran::where('nama_pengeluaran', 'Token PLN Mingguan')->whereDate('tanggal', now()->toDateString())->exists()) {
            Pengeluaran::create([
                'jenis_pengeluaran' => 'Listrik',
                'nama_pengeluaran' => 'Token PLN Mingguan',
                'nominal' => 50000,
                'keterangan' => 'Token PLN Mingguan',
                'tanggal' => now(),
            ]);
        }

        if (!Pengeluaran::where('nama_pengeluaran', 'Iuran Kebersihan')->whereDate('tanggal', now()->toDateString())->exists()) {
            Pengeluaran::create([
                'jenis_pengeluaran' => 'Kebersihan',
                'nama_pengeluaran' => 'Iuran Kebersihan',
                'nominal' => 32000,
                'keterangan' => 'Iuran Kebersihan',
                'tanggal' => now(),
            ]);
        }

        $this->command->info('Warkop seeder completed with initial restocks and prices for accurate HPP.');
    }
}
