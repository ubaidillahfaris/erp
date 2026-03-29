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

class BakeryStoreSeeder extends Seeder
{
    /**
     * Seed realistic Bakery Store (Toko Roti) data.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // ─── 1. Units (Satuan) ───────────────────────────────────
            // Use existing units where available, create only missing ones
            $satuanKg = Satuan::where('simbol', 'kg')->first();
            $satuanGr = Satuan::where('simbol', 'gr')->first();
            $satuanPcs = Satuan::where('simbol', 'pcs')->first();

            // Mililiter — existing DB has 'mil', reuse it
            $satuanMl = Satuan::where('simbol', 'mil')->first();
            if (! $satuanMl) {
                $satuanMl = Satuan::firstOrCreate(
                    ['simbol' => 'ml'],
                    ['nama' => 'Mililiter', 'deskripsi' => 'Volume dalam Mililiter'],
                );
            }

            // Butir — for eggs
            $satuanButir = Satuan::firstOrCreate(
                ['simbol' => 'butir'],
                ['nama' => 'Butir', 'deskripsi' => 'Satuan untuk telur'],
            );

            // Box — for packaging
            $satuanBox = Satuan::firstOrCreate(
                ['simbol' => 'box'],
                ['nama' => 'Box', 'deskripsi' => 'Satuan kotak/kemasan'],
            );

            // ─── 2. Vendors (Suppliers) ──────────────────────────────
            $vendorSembako = Vendor::create([
                'nama' => 'Sembako Jaya Utama',
                'alamat' => 'Jl. Pasar Baru No. 12, Jakarta',
                'telepon' => '021-5551234',
                'email' => 'sales@sembakojaya.com',
                'keterangan' => 'Supplier tepung, gula, dan bahan kering skala besar',
            ]);

            $vendorDairy = Vendor::create([
                'nama' => 'Dairy Fresh Indonesia',
                'alamat' => 'Kawasan Industri Sentul, Bogor',
                'telepon' => '021-8884321',
                'email' => 'info@dairyfresh.co.id',
                'keterangan' => 'Supplier susu, mentega, dan keju berkualitas',
            ]);

            $vendorTernak = Vendor::create([
                'nama' => 'Peternakan Berkah',
                'alamat' => 'Desa Sukamaju, Jawa Barat',
                'telepon' => '0812-3456-7890',
                'email' => 'admin@berkahternak.com',
                'keterangan' => 'Supplier telur ayam fresh harian',
            ]);

            // ─── 3. Products — Raw Materials ─────────────────────────
            $rawMaterials = [
                [
                    'sku' => 'RAW-TEPUNG-001',
                    'nama' => 'Tepung Terigu Cakra Kembar',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Tepung protein tinggi untuk roti dan pastry',
                    'satuan' => $satuanKg,
                    'purchase_price' => 12500,
                    'initial_stock' => 50,
                    'stok_minimal' => 10,
                ],
                [
                    'sku' => 'RAW-TEPUNG-002',
                    'nama' => 'Tepung Terigu Segitiga Biru',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Tepung protein sedang untuk kue dan donat',
                    'satuan' => $satuanKg,
                    'purchase_price' => 11000,
                    'initial_stock' => 30,
                    'stok_minimal' => 5,
                ],
                [
                    'sku' => 'RAW-GULA-001',
                    'nama' => 'Gula Pasir Rose Brand',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Gula pasir halus untuk adonan roti',
                    'satuan' => $satuanKg,
                    'purchase_price' => 16000,
                    'initial_stock' => 20,
                    'stok_minimal' => 5,
                ],
                [
                    'sku' => 'RAW-GULA-002',
                    'nama' => 'Gula Halus / Icing Sugar',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Gula halus untuk topping dan glaze',
                    'satuan' => $satuanKg,
                    'purchase_price' => 22000,
                    'initial_stock' => 5,
                    'stok_minimal' => 2,
                ],
                [
                    'sku' => 'RAW-MARGARIN-001',
                    'nama' => 'Margarin Blue Band',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Margarin serbaguna untuk adonan roti',
                    'satuan' => $satuanKg,
                    'purchase_price' => 45000,
                    'initial_stock' => 10,
                    'stok_minimal' => 3,
                ],
                [
                    'sku' => 'RAW-BUTTER-001',
                    'nama' => 'Butter Anchor Unsalted',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Mentega impor untuk croissant dan danish',
                    'satuan' => $satuanKg,
                    'purchase_price' => 120000,
                    'initial_stock' => 5,
                    'stok_minimal' => 2,
                ],
                [
                    'sku' => 'RAW-RAGI-001',
                    'nama' => 'Ragi Instan Fermipan',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Ragi instan untuk fermentasi adonan roti',
                    'satuan' => $satuanGr,
                    'purchase_price' => 500, // per gram
                    'initial_stock' => 500,
                    'stok_minimal' => 100,
                ],
                [
                    'sku' => 'RAW-TELUR-001',
                    'nama' => 'Telur Ayam Fresh',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Telur ayam segar untuk adonan',
                    'satuan' => $satuanButir,
                    'purchase_price' => 2500, // per butir
                    'initial_stock' => 120,
                    'stok_minimal' => 30,
                ],
                [
                    'sku' => 'RAW-SUSU-001',
                    'nama' => 'Susu Cair Full Cream',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Susu cair untuk adonan roti dan kue',
                    'satuan' => $satuanMl,
                    'purchase_price' => 20, // per ml
                    'initial_stock' => 5000,
                    'stok_minimal' => 1000,
                ],
                [
                    'sku' => 'RAW-COKELAT-001',
                    'nama' => 'Dark Chocolate Compound',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Cokelat compound untuk coating dan filling',
                    'satuan' => $satuanKg,
                    'purchase_price' => 85000,
                    'initial_stock' => 5,
                    'stok_minimal' => 2,
                ],
                [
                    'sku' => 'RAW-KEJU-001',
                    'nama' => 'Keju Cheddar Kraft',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Keju cheddar untuk filling roti',
                    'satuan' => $satuanKg,
                    'purchase_price' => 95000,
                    'initial_stock' => 3,
                    'stok_minimal' => 1,
                ],
                [
                    'sku' => 'RAW-GARAM-001',
                    'nama' => 'Garam Halus Refina',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Garam halus untuk adonan roti',
                    'satuan' => $satuanGr,
                    'purchase_price' => 15, // per gram
                    'initial_stock' => 1000,
                    'stok_minimal' => 200,
                ],
                [
                    'sku' => 'RAW-SUSU-002',
                    'nama' => 'Susu Bubuk Full Cream',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Susu bubuk untuk memperkaya rasa roti',
                    'satuan' => $satuanGr,
                    'purchase_price' => 120, // per gram
                    'initial_stock' => 500,
                    'stok_minimal' => 100,
                ],
                [
                    'sku' => 'RAW-VANILI-001',
                    'nama' => 'Vanili Bubuk',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Perasa vanili untuk adonan kue dan roti',
                    'satuan' => $satuanGr,
                    'purchase_price' => 200, // per gram
                    'initial_stock' => 100,
                    'stok_minimal' => 20,
                ],
                [
                    'sku' => 'RAW-MINYAK-001',
                    'nama' => 'Minyak Goreng Bimoli',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Minyak goreng untuk menggoreng donat',
                    'satuan' => $satuanMl,
                    'purchase_price' => 18, // per ml
                    'initial_stock' => 5000,
                    'stok_minimal' => 1000,
                ],
                [
                    'sku' => 'RAW-SELAI-001',
                    'nama' => 'Selai Strawberry',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Selai buah untuk isian roti',
                    'satuan' => $satuanGr,
                    'purchase_price' => 60, // per gram
                    'initial_stock' => 1000,
                    'stok_minimal' => 200,
                ],
                [
                    'sku' => 'RAW-SELAI-002',
                    'nama' => 'Selai Cokelat Nutella',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Selai cokelat hazelnut premium',
                    'satuan' => $satuanGr,
                    'purchase_price' => 150, // per gram
                    'initial_stock' => 500,
                    'stok_minimal' => 100,
                ],
                [
                    'sku' => 'RAW-ALMOND-001',
                    'nama' => 'Almond Slice',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Irisan almond untuk topping pastry',
                    'satuan' => $satuanGr,
                    'purchase_price' => 250, // per gram
                    'initial_stock' => 300,
                    'stok_minimal' => 50,
                ],
                [
                    'sku' => 'RAW-WIJEN-001',
                    'nama' => 'Biji Wijen Putih',
                    'kategori' => 'Bahan Baku',
                    'deskripsi' => 'Wijen untuk taburan roti',
                    'satuan' => $satuanGr,
                    'purchase_price' => 80, // per gram
                    'initial_stock' => 500,
                    'stok_minimal' => 100,
                ],
            ];

            $materialModels = [];
            foreach ($rawMaterials as $data) {
                $produk = Produk::create([
                    'sku' => $data['sku'],
                    'nama' => $data['nama'],
                    'kategori' => $data['kategori'],
                    'deskripsi' => $data['deskripsi'],
                    'satuan_id' => $data['satuan']->id,
                    'type' => 'raw_material',
                    'track_stock' => true,
                    'is_active' => true,
                    'stok_minimal' => $data['stok_minimal'],
                ]);

                Price::create([
                    'produk_id' => $produk->id,
                    'satuan_id' => $data['satuan']->id,
                    'purchase_price' => $data['purchase_price'],
                    'retail_price' => 0,
                    'is_current' => true,
                ]);

                Stock::create([
                    'produk_id' => $produk->id,
                    'last_satuan_id' => $data['satuan']->id,
                    'balance' => $data['initial_stock'],
                ]);

                $materialModels[$data['sku']] = $produk;
            }

            // ─── 4. Products — Intermediate & Finished Goods ──────────
            $intermediateGoods = [
                [
                    'sku' => 'INT-ADONAN-001',
                    'nama' => 'Adonan Dasar Roti Manis',
                    'kategori' => 'Adonan',
                    'deskripsi' => 'Adonan dasar siap bentuk untuk aneka roti manis',
                    'satuan_id' => $satuanGr->id,
                    'initial_stock' => 0,
                    'stok_minimal' => 0,
                    'expected_yield' => 1000,
                    'recipe' => [
                        'RAW-TEPUNG-001' => ['jumlah' => 0.500],
                        'RAW-GULA-001' => ['jumlah' => 0.100],
                        'RAW-MARGARIN-001' => ['jumlah' => 0.050],
                        'RAW-RAGI-001' => ['jumlah' => 11],
                        'RAW-TELUR-001' => ['jumlah' => 1],
                        'RAW-SUSU-001' => ['jumlah' => 200],
                        'RAW-GARAM-001' => ['jumlah' => 5],
                    ],
                ]
            ];

            $finishedGoods = [
                [
                    'sku' => 'FG-ROTITAWAR-001',
                    'nama' => 'Roti Tawar Spesial',
                    'kategori' => 'Roti',
                    'deskripsi' => 'Roti tawar premium lembut, 1 loyang isi 10 slice',
                    'retail_price' => 18000,
                    'initial_stock' => 10,
                    'stok_minimal' => 3,
                    'expected_yield' => 1,
                    'recipe' => [
                        'RAW-TEPUNG-001' => ['jumlah' => 0.450],
                        'RAW-GULA-001' => ['jumlah' => 0.050],
                        'RAW-MARGARIN-001' => ['jumlah' => 0.030],
                        'RAW-RAGI-001' => ['jumlah' => 11],
                        'RAW-TELUR-001' => ['jumlah' => 1],
                        'RAW-SUSU-001' => ['jumlah' => 250],
                        'RAW-GARAM-001' => ['jumlah' => 8],
                        'RAW-SUSU-002' => ['jumlah' => 20],
                    ],
                ],
                [
                    'sku' => 'FG-ROTITAWAR-002',
                    'nama' => 'Roti Tawar Gandum',
                    'kategori' => 'Roti',
                    'deskripsi' => 'Roti tawar whole wheat untuk pilihan sehat',
                    'retail_price' => 22000,
                    'initial_stock' => 8,
                    'stok_minimal' => 2,
                    'expected_yield' => 1,
                    'recipe' => [
                        'RAW-TEPUNG-001' => ['jumlah' => 0.300],
                        'RAW-TEPUNG-002' => ['jumlah' => 0.200],
                        'RAW-GULA-001' => ['jumlah' => 0.030],
                        'RAW-MARGARIN-001' => ['jumlah' => 0.025],
                        'RAW-RAGI-001' => ['jumlah' => 11],
                        'RAW-TELUR-001' => ['jumlah' => 1],
                        'RAW-SUSU-001' => ['jumlah' => 200],
                        'RAW-GARAM-001' => ['jumlah' => 8],
                        'RAW-WIJEN-001' => ['jumlah' => 10],
                    ],
                ],
                [
                    'sku' => 'FG-ROTISOBEK-001',
                    'nama' => 'Roti Sobek Cokelat',
                    'kategori' => 'Roti',
                    'deskripsi' => 'Roti sobek lembut dengan isian cokelat, isi 6 potong',
                    'retail_price' => 25000,
                    'initial_stock' => 12,
                    'stok_minimal' => 3,
                    'expected_yield' => 1,
                    'recipe' => [
                        'RAW-TEPUNG-001' => ['jumlah' => 0.400],
                        'RAW-GULA-001' => ['jumlah' => 0.060],
                        'RAW-MARGARIN-001' => ['jumlah' => 0.040],
                        'RAW-RAGI-001' => ['jumlah' => 10],
                        'RAW-TELUR-001' => ['jumlah' => 2],
                        'RAW-SUSU-001' => ['jumlah' => 200],
                        'RAW-SELAI-002' => ['jumlah' => 60],
                    ],
                ],
                [
                    'sku' => 'FG-ROTIMANIS-001',
                    'nama' => 'Roti Manis Isi Keju',
                    'kategori' => 'Roti',
                    'deskripsi' => 'Roti manis empuk dengan isian keju leleh',
                    'retail_price' => 7000,
                    'initial_stock' => 20,
                    'stok_minimal' => 5,
                    'expected_yield' => 8,
                    'recipe' => [
                        'RAW-TEPUNG-001' => ['jumlah' => 0.500],
                        'RAW-GULA-001' => ['jumlah' => 0.080],
                        'RAW-MARGARIN-001' => ['jumlah' => 0.050],
                        'RAW-RAGI-001' => ['jumlah' => 11],
                        'RAW-TELUR-001' => ['jumlah' => 2],
                        'RAW-SUSU-001' => ['jumlah' => 150],
                        'RAW-KEJU-001' => ['jumlah' => 0.100],
                        'RAW-SUSU-002' => ['jumlah' => 20],
                    ],
                ],
                [
                    'sku' => 'FG-ROTIMANIS-003',
                    'nama' => 'Roti Manis Cokelat Premium',
                    'kategori' => 'Roti',
                    'deskripsi' => 'Roti manis empuk menggunakan adonan dasar',
                    'retail_price' => 8000,
                    'initial_stock' => 10,
                    'stok_minimal' => 5,
                    'expected_yield' => 10,
                    'recipe' => [
                        'INT-ADONAN-001' => ['jumlah' => 500],
                        'RAW-SELAI-002' => ['jumlah' => 100],
                    ],
                ],
                [
                    'sku' => 'FG-DONAT-001',
                    'nama' => 'Donat Chocolate Glaze',
                    'kategori' => 'Kue',
                    'deskripsi' => 'Donat empuk dengan lapisan cokelat glaze',
                    'retail_price' => 8500,
                    'initial_stock' => 24,
                    'stok_minimal' => 6,
                    'expected_yield' => 12,
                    'recipe' => [
                        'RAW-TEPUNG-002' => ['jumlah' => 0.500],
                        'RAW-GULA-001' => ['jumlah' => 0.100],
                        'RAW-MARGARIN-001' => ['jumlah' => 0.050],
                        'RAW-RAGI-001' => ['jumlah' => 8],
                        'RAW-TELUR-001' => ['jumlah' => 2],
                        'RAW-SUSU-001' => ['jumlah' => 150],
                        'RAW-COKELAT-001' => ['jumlah' => 0.150],
                        'RAW-MINYAK-001' => ['jumlah' => 500],
                        'RAW-VANILI-001' => ['jumlah' => 2],
                    ],
                ],
            ];

            $satuanPcsId = $satuanPcs->id;
            $productionModels = [];

            // Merge and Create Products
            $allBOMProducts = array_merge($intermediateGoods, $finishedGoods);

            foreach ($allBOMProducts as $data) {
                $type = str_starts_with($data['sku'], 'FG-') ? 'finished_good' : 'intermediate_good';

                $produk = Produk::create([
                    'sku' => $data['sku'],
                    'nama' => $data['nama'],
                    'kategori' => $data['kategori'],
                    'deskripsi' => $data['deskripsi'],
                    'satuan_id' => $data['satuan_id'] ?? $satuanPcsId,
                    'type' => $type,
                    'track_stock' => true,
                    'is_active' => true,
                    'stok_minimal' => $data['stok_minimal'] ?? 0,
                ]);

                Price::create([
                    'produk_id' => $produk->id,
                    'satuan_id' => $produk->satuan_id,
                    'purchase_price' => 0,
                    'retail_price' => $data['retail_price'] ?? 0,
                    'is_current' => true,
                ]);

                Stock::create([
                    'produk_id' => $produk->id,
                    'last_satuan_id' => $produk->satuan_id,
                    'balance' => $data['initial_stock'] ?? 0,
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

                app(RecalculateHpp::class)->handle($produk);
                $productionModels[$data['sku']] = ['produk' => $produk, 'bom' => $bom];
            }

            // ─── 7. Productions ──────────────────────────────────────────
            $pData = [
                [
                    'sku' => 'PRD-'.date('ym').'-0001',
                    'tanggal' => now()->subDays(2),
                    'model' => $productionModels['FG-ROTITAWAR-001'],
                    'target_yield' => 20,
                    'actual_yield' => 20,
                    'status' => 'completed',
                ],
                [
                    'sku' => 'PRD-'.date('ym').'-0002',
                    'tanggal' => now(),
                    'model' => $productionModels['FG-DONAT-001'],
                    'target_yield' => 48,
                    'actual_yield' => 0,
                    'status' => 'in_progress',
                ],
                [
                    'sku' => 'PRD-'.date('ym').'-0003',
                    'tanggal' => now()->subDays(3),
                    'model' => $productionModels['INT-ADONAN-001'],
                    'target_yield' => 1000,
                    'actual_yield' => 1000,
                    'status' => 'completed',
                ],
                [
                    'sku' => 'PRD-'.date('ym').'-0004',
                    'tanggal' => now()->subDays(1),
                    'model' => $productionModels['FG-ROTIMANIS-003'],
                    'target_yield' => 20,
                    'actual_yield' => 20,
                    'status' => 'completed',
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

                $costSum = 0;
                $scale = $pd['target_yield'] / $m['bom']->expected_yield;

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
