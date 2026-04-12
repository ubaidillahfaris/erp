<?php

namespace Database\Seeders;

use App\Actions\CompleteProduction;
use App\Actions\RecalculateHpp;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Price;
use App\Models\Production;
use App\Models\ProductionItem;
use App\Models\Produk;
use App\Models\Satuan;
use App\Models\Stock;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialAndPlasticSeeder extends Seeder
{
    /**
     * Seed realistic Building Materials and Plastic Manufacturing data.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // ─── 1. Units (Satuan) ───────────────────────────────────
            $satuanKg = Satuan::where('simbol', 'kg')->first();
            $satuanGr = Satuan::where('simbol', 'gr')->first();
            $satuanPcs = Satuan::where('simbol', 'pcs')->first();
            $satuanL = Satuan::where('simbol', 'L')->first();

            $satuanSak = Satuan::firstOrCreate(
                ['simbol' => 'sak'],
                ['nama' => 'Sak', 'deskripsi' => 'Satuan untuk semen/berat 40-50kg'],
            );

            $satuanM3 = Satuan::firstOrCreate(
                ['simbol' => 'm3'],
                ['nama' => 'Meter Kubik', 'deskripsi' => 'Satuan volume material urug'],
            );

            $satuanLonjor = Satuan::firstOrCreate(
                ['simbol' => 'lon'],
                ['nama' => 'Lonjor', 'deskripsi' => 'Satuan untuk besi beton/pipa'],
            );

            $satuanGalon = Satuan::firstOrCreate(
                ['simbol' => 'gal'],
                ['nama' => 'Galon', 'deskripsi' => 'Satuan untuk cat (biasanya 5kg/2.5L)'],
            );

            $satuanRoll = Satuan::firstOrCreate(
                ['simbol' => 'roll'],
                ['nama' => 'Roll', 'deskripsi' => 'Satuan untuk plastik roll/produk panjang'],
            );

            $satuanPack = Satuan::firstOrCreate(
                ['simbol' => 'pack'],
                ['nama' => 'Pack', 'deskripsi' => 'Satuan kemasan isi banyak'],
            );

            // ─── 2. Vendors (Suppliers) ──────────────────────────────
            $vendorSemen = Vendor::create([
                'nama' => 'PT Semen Indonesia (Persero) Tbk',
                'alamat' => 'Gresik, Jawa Timur',
                'telepon' => '031-3981732',
                'email' => 'sales@semenindonesia.com',
                'keterangan' => 'Distributor utama Semen Tiga Roda dan Holcim',
            ]);

            $vendorMaterial = Vendor::create([
                'nama' => 'UD Pasir Berkah Alam',
                'alamat' => 'Jl. Raya Muntilan, Magelang',
                'telepon' => '0812-9988-7766',
                'email' => 'muntilanpasir@gmail.com',
                'keterangan' => 'Supplier pasir merapi dan batu kali',
            ]);

            $vendorPlastik = Vendor::create([
                'nama' => 'PT Global Polimer Indonesia',
                'alamat' => 'Kawasan Industri Jababeka, Cikarang',
                'telepon' => '021-8931234',
                'email' => 'info@globalpolimer.co.id',
                'keterangan' => 'Importir biji plastik HDPE, LDPE, PP',
            ]);

            // ─── 3. Products — Raw Materials & Retail Items ─────────
            $products = [
                // Building Materials (Retail & Stockable)
                [
                    'sku' => 'MAT-SEMEN-001',
                    'nama' => 'Semen Tiga Roda 40kg',
                    'kategori' => 'Material Bangunan',
                    'deskripsi' => 'Semen Portland berkualitas tinggi untuk konstruksi',
                    'satuan' => $satuanSak,
                    'purchase_price' => 58000,
                    'retail_price' => 65000,
                    'initial_stock' => 100,
                    'stok_minimal' => 20,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'MAT-PASIR-001',
                    'nama' => 'Pasir Muntilan (Super)',
                    'kategori' => 'Material Bangunan',
                    'deskripsi' => 'Pasir hitam vulkanik dari lereng Merapi',
                    'satuan' => $satuanM3,
                    'purchase_price' => 220000,
                    'retail_price' => 280000,
                    'initial_stock' => 50,
                    'stok_minimal' => 10,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'MAT-BESI-001',
                    'nama' => 'Besi Beton 8mm (Polos)',
                    'kategori' => 'Material Bangunan',
                    'deskripsi' => 'Besi beton standar SNI, panjang 12m',
                    'satuan' => $satuanLonjor,
                    'purchase_price' => 45000,
                    'retail_price' => 52000,
                    'initial_stock' => 200,
                    'stok_minimal' => 50,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'MAT-CAT-001',
                    'nama' => 'Avian Emulsion White 5kg',
                    'kategori' => 'Cat & Finishing',
                    'deskripsi' => 'Cat tembok interior warna putih bersih',
                    'satuan' => $satuanGalon,
                    'purchase_price' => 125000,
                    'retail_price' => 145000,
                    'initial_stock' => 30,
                    'stok_minimal' => 5,
                    'type' => 'raw_material',
                ],

                // Plastic Raw Materials
                [
                    'sku' => 'RAW-PLAS-HDPE',
                    'nama' => 'Biji Plastik HDPE Virgin',
                    'kategori' => 'Bahan Baku Plastik',
                    'deskripsi' => 'High Density Polyethylene untuk kantong kresek',
                    'satuan' => $satuanKg,
                    'purchase_price' => 18500,
                    'retail_price' => 0,
                    'initial_stock' => 1000,
                    'stok_minimal' => 200,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'RAW-PLAS-LDPE',
                    'nama' => 'Biji Plastik LDPE Virgin',
                    'kategori' => 'Bahan Baku Plastik',
                    'deskripsi' => 'Low Density Polyethylene untuk botol dan plastik lentur',
                    'satuan' => $satuanKg,
                    'purchase_price' => 19500,
                    'retail_price' => 0,
                    'initial_stock' => 500,
                    'stok_minimal' => 100,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'RAW-PIGM-WHT',
                    'nama' => 'Pigmen Putih (Titanium Dioxide)',
                    'kategori' => 'Bahan Penolong',
                    'deskripsi' => 'Pewarna putih untuk produk plastik',
                    'satuan' => $satuanGr,
                    'purchase_price' => 150, // per gram
                    'retail_price' => 0,
                    'initial_stock' => 5000,
                    'stok_minimal' => 1000,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'RAW-PIGM-BLK',
                    'nama' => 'Pigmen Hitam (Carbon Black)',
                    'kategori' => 'Bahan Penolong',
                    'deskripsi' => 'Pewarna hitam untuk produk plastik',
                    'satuan' => $satuanGr,
                    'purchase_price' => 120, // per gram
                    'retail_price' => 0,
                    'initial_stock' => 3000,
                    'stok_minimal' => 500,
                    'type' => 'raw_material',
                ],
            ];

            $materialModels = [];
            foreach ($products as $data) {
                $produk = Produk::create([
                    'sku' => $data['sku'],
                    'nama' => $data['nama'],
                    'kategori' => $data['kategori'],
                    'deskripsi' => $data['deskripsi'],
                    'satuan_id' => $data['satuan']->id,
                    'type' => $data['type'],
                    'track_stock' => true,
                    'is_active' => true,
                    'stok_minimal' => $data['stok_minimal'],
                ]);

                Price::create([
                    'produk_id' => $produk->id,
                    'satuan_id' => $data['satuan']->id,
                    'purchase_price' => $data['purchase_price'],
                    'retail_price' => $data['retail_price'],
                    'is_current' => true,
                ]);

                Stock::create([
                    'produk_id' => $produk->id,
                    'last_satuan_id' => $data['satuan']->id,
                    'balance' => $data['initial_stock'],
                ]);

                $materialModels[$data['sku']] = $produk;
            }

            // ─── 4. Products — Finished Goods (Plastic) ──────────
            $finishedGoods = [
                [
                    'sku' => 'FG-PLAS-WHT24',
                    'nama' => 'Kantong Kresek HD Putih Uk. 24',
                    'kategori' => 'Produk Plastik',
                    'deskripsi' => 'Kantong plastik putih ukuran 24 (isi ±50 lembar)',
                    'retail_price' => 12500,
                    'initial_stock' => 50,
                    'stok_minimal' => 20,
                    'satuan_id' => $satuanPack->id,
                    'expected_yield' => 1,
                    'recipe' => [
                        'RAW-PLAS-HDPE' => ['jumlah' => 0.480], // 480 grams
                        'RAW-PIGM-WHT' => ['jumlah' => 20],   // 20 grams
                    ],
                ],
                [
                    'sku' => 'FG-PLAS-BLK24',
                    'nama' => 'Kantong Kresek HD Hitam Uk. 24',
                    'kategori' => 'Produk Plastik',
                    'deskripsi' => 'Kantong plastik hitam ukuran 24 (isi ±50 lembar)',
                    'retail_price' => 10500,
                    'initial_stock' => 80,
                    'stok_minimal' => 20,
                    'satuan_id' => $satuanPack->id,
                    'expected_yield' => 1,
                    'recipe' => [
                        'RAW-PLAS-HDPE' => ['jumlah' => 0.490], // 490 grams
                        'RAW-PIGM-BLK' => ['jumlah' => 10],   // 10 grams
                    ],
                ],
                [
                    'sku' => 'FG-BOTL-600',
                    'nama' => 'Botol Plastik PET 600ml',
                    'kategori' => 'Produk Plastik',
                    'deskripsi' => 'Botol transparan polos 600ml untuk minuman',
                    'retail_price' => 1200,
                    'initial_stock' => 1000,
                    'stok_minimal' => 500,
                    'satuan_id' => $satuanPcs->id,
                    'expected_yield' => 100, // Produced in batches of 100
                    'recipe' => [
                        'RAW-PLAS-LDPE' => ['jumlah' => 3.0], // 3kg for 100 bottles -> 30g/bottle
                    ],
                ],
            ];

            $productionModels = [];

            foreach ($finishedGoods as $data) {
                $produk = Produk::create([
                    'sku' => $data['sku'],
                    'nama' => $data['nama'],
                    'kategori' => $data['kategori'],
                    'deskripsi' => $data['deskripsi'],
                    'satuan_id' => $data['satuan_id'],
                    'type' => 'finished_good',
                    'track_stock' => true,
                    'is_active' => true,
                    'stok_minimal' => $data['stok_minimal'],
                ]);

                Price::create([
                    'produk_id' => $produk->id,
                    'satuan_id' => $produk->satuan_id,
                    'purchase_price' => 0,
                    'retail_price' => $data['retail_price'],
                    'is_current' => true,
                ]);

                Stock::create([
                    'produk_id' => $produk->id,
                    'last_satuan_id' => $produk->satuan_id,
                    'balance' => $data['initial_stock'],
                ]);

                $bom = Bom::create([
                    'produk_id' => $produk->id,
                    'sku' => 'BOM-'.$data['sku'],
                    'nama' => 'Resep '.$data['nama'],
                    'is_active' => true,
                    'expected_yield' => $data['expected_yield'],
                    'auto_deduct_on_sale' => true,
                ]);

                foreach ($data['recipe'] as $materialSku => $itemData) {
                    $material = $materialModels[$materialSku] ?? Produk::where('sku', $materialSku)->first();
                    if ($material) {
                        BomItem::create([
                            'bom_id' => $bom->id,
                            'produk_id' => $material->id,
                            'satuan_id' => $material->satuan_id,
                            'jumlah' => $itemData['jumlah'],
                        ]);
                    }
                }

                // Recalculate HPP based on ingredients
                app(RecalculateHpp::class)->handle($produk);
                $productionModels[$data['sku']] = ['produk' => $produk, 'bom' => $bom];
            }

            // ─── 5. Initial Productions (Optional History) ──────────
            $pData = [
                [
                    'sku' => 'PROD-PLAS-'.date('ym').'-001',
                    'tanggal' => now()->subDays(1),
                    'model' => $productionModels['FG-PLAS-WHT24'],
                    'target_yield' => 100,
                    'actual_yield' => 100,
                    'status' => 'completed',
                ],
                [
                    'sku' => 'PROD-BOTL-'.date('ym').'-001',
                    'tanggal' => now(),
                    'model' => $productionModels['FG-BOTL-600'],
                    'target_yield' => 500,
                    'actual_yield' => 0,
                    'status' => 'in_progress',
                ],
            ];

            foreach ($pData as $pd) {
                $m = $pd['model'];
                $prod = Production::create([
                    'sku' => $pd['sku'],
                    'tanggal' => $pd['tanggal'],
                    'bom_id' => $m['bom']->id,
                    'produk_id' => $m['produk']->id,
                    'target_yield' => $pd['target_yield'],
                    'actual_yield' => $pd['actual_yield'] ?: null,
                    'status' => $pd['status'],
                    'total_cost' => 0,
                ]);

                $scale = $pd['target_yield'] / $m['bom']->expected_yield;
                $costSum = 0;

                foreach ($m['bom']->items as $item) {
                    $itemPrice = $item->produk->currentPrice->purchase_price ?? 0;
                    $qty = $item->jumlah * $scale;
                    
                    if ($pd['status'] === 'completed') {
                        $costSum += ($itemPrice * $qty);
                    }

                    ProductionItem::create([
                        'production_id' => $prod->id,
                        'produk_id' => $item->produk_id,
                        'satuan_id' => $item->satuan_id,
                        'planned_qty' => $qty,
                        'actual_qty' => ($pd['status'] === 'completed') ? $qty : 0,
                        'harga_satuan' => $itemPrice,
                    ]);
                }

                if ($pd['status'] === 'completed') {
                    $prod->update(['total_cost' => $costSum, 'actual_yield' => $pd['actual_yield']]);
                    app(CompleteProduction::class)->handle($prod);
                }
            }
        });
    }
}
