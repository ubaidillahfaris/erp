<?php

namespace Database\Seeders;

use App\Actions\CompleteProduction;
use App\Actions\RecalculateHpp;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionItem;
use App\Models\Stock;
use App\Models\Unit;
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
            // ─── 1. Units (Unit) ───────────────────────────────────
            // Use existing units where available, create only missing ones
            $unitKg = Unit::where('symbol', 'kg')->first();
            $unitGr = Unit::where('symbol', 'gr')->first();
            $unitPcs = Unit::where('symbol', 'pcs')->first();

            // Mililiter — existing DB has 'mil', reuse it
            $unitMl = Unit::where('symbol', 'mil')->first();
            if (! $unitMl) {
                $unitMl = Unit::firstOrCreate(
                    ['symbol' => 'ml'],
                    ['name' => 'Mililiter', 'description' => 'Volume dalam Mililiter'],
                );
            }

            // Butir — for eggs
            $unitButir = Unit::firstOrCreate(
                ['symbol' => 'butir'],
                ['name' => 'Butir', 'description' => 'Unit untuk telur'],
            );

            // Box — for packaging
            $unitBox = Unit::firstOrCreate(
                ['symbol' => 'box'],
                ['name' => 'Box', 'description' => 'Unit kotak/kemasan'],
            );

            // ─── 2. Categories ──────────────────────────────────────
            $catBahanBaku = Category::firstOrCreate(['name' => 'Raw Materials'], ['slug' => 'bahan-baku']);
            $catAdonan = Category::firstOrCreate(['name' => 'Adonan'], ['slug' => 'adonan']);
            $catRoti = Category::firstOrCreate(['name' => 'Roti'], ['slug' => 'roti']);
            $catKue = Category::firstOrCreate(['name' => 'Kue'], ['slug' => 'kue']);

            // ─── 3. Vendors (Suppliers) ──────────────────────────────
            $vendorSembako = Vendor::create([
                'name' => 'Sembako Jaya Utama',
                'address' => 'Jl. Pasar Baru No. 12, Jakarta',
                'phone' => '021-5551234',
                'email' => 'sales@sembakojaya.com',
                'notes' => 'Supplier tepung, gula, dan bahan kering skala besar',
            ]);

            $vendorDairy = Vendor::create([
                'name' => 'Dairy Fresh Indonesia',
                'address' => 'Kawasan Industri Sentul, Bogor',
                'phone' => '021-8884321',
                'email' => 'info@dairyfresh.co.id',
                'notes' => 'Supplier susu, mentega, dan keju berkualitas',
            ]);

            $vendorTernak = Vendor::create([
                'name' => 'Peternakan Berkah',
                'address' => 'Desa Sukamaju, Jawa Barat',
                'phone' => '0812-3456-7890',
                'email' => 'admin@berkahternak.com',
                'notes' => 'Supplier telur ayam fresh harian',
            ]);

            // ─── 3. Products — Raw Materials ─────────────────────────
            $rawMaterials = [
                [
                    'sku' => 'RAW-TEPUNG-001',
                    'name' => 'Tepung Terigu Cakra Kembar',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Tepung protein tinggi untuk roti dan pastry',
                    'unit' => $unitKg,
                    'purchase_price' => 12500,
                    'initial_stock' => 50,
                    'min_stock' => 10,
                ],
                [
                    'sku' => 'RAW-TEPUNG-002',
                    'name' => 'Tepung Terigu Segitiga Biru',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Tepung protein sedang untuk kue dan donat',
                    'unit' => $unitKg,
                    'purchase_price' => 11000,
                    'initial_stock' => 30,
                    'min_stock' => 5,
                ],
                [
                    'sku' => 'RAW-GULA-001',
                    'name' => 'Gula Pasir Rose Brand',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Gula pasir halus untuk adonan roti',
                    'unit' => $unitKg,
                    'purchase_price' => 16000,
                    'initial_stock' => 20,
                    'min_stock' => 5,
                ],
                [
                    'sku' => 'RAW-GULA-002',
                    'name' => 'Gula Halus / Icing Sugar',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Gula halus untuk topping dan glaze',
                    'unit' => $unitKg,
                    'purchase_price' => 22000,
                    'initial_stock' => 5,
                    'min_stock' => 2,
                ],
                [
                    'sku' => 'RAW-MARGARIN-001',
                    'name' => 'Margarin Blue Band',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Margarin serbaguna untuk adonan roti',
                    'unit' => $unitKg,
                    'purchase_price' => 45000,
                    'initial_stock' => 10,
                    'min_stock' => 3,
                ],
                [
                    'sku' => 'RAW-BUTTER-001',
                    'name' => 'Butter Anchor Unsalted',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Mentega impor untuk croissant dan danish',
                    'unit' => $unitKg,
                    'purchase_price' => 120000,
                    'initial_stock' => 5,
                    'min_stock' => 2,
                ],
                [
                    'sku' => 'RAW-RAGI-001',
                    'name' => 'Ragi Instan Fermipan',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Ragi instan untuk fermentasi adonan roti',
                    'unit' => $unitGr,
                    'purchase_price' => 500, // per gram
                    'initial_stock' => 500,
                    'min_stock' => 100,
                ],
                [
                    'sku' => 'RAW-TELUR-001',
                    'name' => 'Telur Ayam Fresh',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Telur ayam segar untuk adonan',
                    'unit' => $unitButir,
                    'purchase_price' => 2500, // per butir
                    'initial_stock' => 120,
                    'min_stock' => 30,
                ],
                [
                    'sku' => 'RAW-SUSU-001',
                    'name' => 'Susu Cair Full Cream',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Susu cair untuk adonan roti dan kue',
                    'unit' => $unitMl,
                    'purchase_price' => 20, // per ml
                    'initial_stock' => 5000,
                    'min_stock' => 1000,
                ],
                [
                    'sku' => 'RAW-COKELAT-001',
                    'name' => 'Dark Chocolate Compound',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Cokelat compound untuk coating dan filling',
                    'unit' => $unitKg,
                    'purchase_price' => 85000,
                    'initial_stock' => 5,
                    'min_stock' => 2,
                ],
                [
                    'sku' => 'RAW-KEJU-001',
                    'name' => 'Keju Cheddar Kraft',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Keju cheddar untuk filling roti',
                    'unit' => $unitKg,
                    'purchase_price' => 95000,
                    'initial_stock' => 3,
                    'min_stock' => 1,
                ],
                [
                    'sku' => 'RAW-GARAM-001',
                    'name' => 'Garam Halus Refina',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Garam halus untuk adonan roti',
                    'unit' => $unitGr,
                    'purchase_price' => 15, // per gram
                    'initial_stock' => 1000,
                    'min_stock' => 200,
                ],
                [
                    'sku' => 'RAW-SUSU-002',
                    'name' => 'Susu Bubuk Full Cream',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Susu bubuk untuk memperkaya rasa roti',
                    'unit' => $unitGr,
                    'purchase_price' => 120, // per gram
                    'initial_stock' => 500,
                    'min_stock' => 100,
                ],
                [
                    'sku' => 'RAW-VANILI-001',
                    'name' => 'Vanili Bubuk',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Perasa vanili untuk adonan kue dan roti',
                    'unit' => $unitGr,
                    'purchase_price' => 200, // per gram
                    'initial_stock' => 100,
                    'min_stock' => 20,
                ],
                [
                    'sku' => 'RAW-MINYAK-001',
                    'name' => 'Minyak Goreng Bimoli',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Minyak goreng untuk menggoreng donat',
                    'unit' => $unitMl,
                    'purchase_price' => 18, // per ml
                    'initial_stock' => 5000,
                    'min_stock' => 1000,
                ],
                [
                    'sku' => 'RAW-SELAI-001',
                    'name' => 'Selai Strawberry',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Selai buah untuk isian roti',
                    'unit' => $unitGr,
                    'purchase_price' => 60, // per gram
                    'initial_stock' => 1000,
                    'min_stock' => 200,
                ],
                [
                    'sku' => 'RAW-SELAI-002',
                    'name' => 'Selai Cokelat Nutella',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Selai cokelat hazelnut premium',
                    'unit' => $unitGr,
                    'purchase_price' => 150, // per gram
                    'initial_stock' => 500,
                    'min_stock' => 100,
                ],
                [
                    'sku' => 'RAW-ALMOND-001',
                    'name' => 'Almond Slice',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Irisan almond untuk topping pastry',
                    'unit' => $unitGr,
                    'purchase_price' => 250, // per gram
                    'initial_stock' => 300,
                    'min_stock' => 50,
                ],
                [
                    'sku' => 'RAW-WIJEN-001',
                    'name' => 'Biji Wijen Putih',
                    'category_id' => $catBahanBaku->id,
                    'description' => 'Wijen untuk taburan roti',
                    'unit' => $unitGr,
                    'purchase_price' => 80, // per gram
                    'initial_stock' => 500,
                    'min_stock' => 100,
                ],
            ];

            $materialModels = [];
            foreach ($rawMaterials as $data) {
                $product = Product::create([
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'kategori' => $data['kategori'],
                    'description' => $data['description'],
                    'unit_id' => $data['unit']->id,
                    'type' => 'raw_material',
                    'track_stock' => true,
                    'is_active' => true,
                    'min_stock' => $data['min_stock'],
                ]);

                Price::create([
                    'product_id' => $product->id,
                    'unit_id' => $data['unit']->id,
                    'purchase_price' => $data['purchase_price'],
                    'retail_price' => 0,
                    'is_current' => true,
                ]);

                Stock::create([
                    'product_id' => $product->id,
                    'last_unit_id' => $data['unit']->id,
                    'balance' => $data['initial_stock'],
                ]);

                $materialModels[$data['sku']] = $product;
            }

            // ─── 4. Products — Intermediate & Finished Goods ──────────
            $intermediateGoods = [
                [
                    'sku' => 'INT-ADONAN-001',
                    'name' => 'Adonan Dasar Roti Manis',
                    'category_id' => $catAdonan->id,
                    'description' => 'Adonan dasar siap bentuk untuk aneka roti manis',
                    'unit_id' => $unitGr->id,
                    'initial_stock' => 0,
                    'min_stock' => 0,
                    'expected_yield' => 1000,
                    'recipe' => [
                        'RAW-TEPUNG-001' => ['quantity' => 0.500],
                        'RAW-GULA-001' => ['quantity' => 0.100],
                        'RAW-MARGARIN-001' => ['quantity' => 0.050],
                        'RAW-RAGI-001' => ['quantity' => 11],
                        'RAW-TELUR-001' => ['quantity' => 1],
                        'RAW-SUSU-001' => ['quantity' => 200],
                        'RAW-GARAM-001' => ['quantity' => 5],
                    ],
                ],
            ];

            $finishedGoods = [
                [
                    'sku' => 'FG-ROTITAWAR-001',
                    'name' => 'Roti Tawar Spesial',
                    'category_id' => $catRoti->id,
                    'description' => 'Roti tawar premium lembut, 1 loyang isi 10 slice',
                    'retail_price' => 18000,
                    'initial_stock' => 10,
                    'min_stock' => 3,
                    'expected_yield' => 1,
                    'recipe' => [
                        'RAW-TEPUNG-001' => ['quantity' => 0.450],
                        'RAW-GULA-001' => ['quantity' => 0.050],
                        'RAW-MARGARIN-001' => ['quantity' => 0.030],
                        'RAW-RAGI-001' => ['quantity' => 11],
                        'RAW-TELUR-001' => ['quantity' => 1],
                        'RAW-SUSU-001' => ['quantity' => 250],
                        'RAW-GARAM-001' => ['quantity' => 8],
                        'RAW-SUSU-002' => ['quantity' => 20],
                    ],
                ],
                [
                    'sku' => 'FG-ROTITAWAR-002',
                    'name' => 'Roti Tawar Gandum',
                    'category_id' => $catRoti->id,
                    'description' => 'Roti tawar whole wheat untuk pilihan sehat',
                    'retail_price' => 22000,
                    'initial_stock' => 8,
                    'min_stock' => 2,
                    'expected_yield' => 1,
                    'recipe' => [
                        'RAW-TEPUNG-001' => ['quantity' => 0.300],
                        'RAW-TEPUNG-002' => ['quantity' => 0.200],
                        'RAW-GULA-001' => ['quantity' => 0.030],
                        'RAW-MARGARIN-001' => ['quantity' => 0.025],
                        'RAW-RAGI-001' => ['quantity' => 11],
                        'RAW-TELUR-001' => ['quantity' => 1],
                        'RAW-SUSU-001' => ['quantity' => 200],
                        'RAW-GARAM-001' => ['quantity' => 8],
                        'RAW-WIJEN-001' => ['quantity' => 10],
                    ],
                ],
                [
                    'sku' => 'FG-ROTISOBEK-001',
                    'name' => 'Roti Sobek Cokelat',
                    'category_id' => $catRoti->id,
                    'description' => 'Roti sobek lembut dengan isian cokelat, isi 6 potong',
                    'retail_price' => 25000,
                    'initial_stock' => 12,
                    'min_stock' => 3,
                    'expected_yield' => 1,
                    'recipe' => [
                        'RAW-TEPUNG-001' => ['quantity' => 0.400],
                        'RAW-GULA-001' => ['quantity' => 0.060],
                        'RAW-MARGARIN-001' => ['quantity' => 0.040],
                        'RAW-RAGI-001' => ['quantity' => 10],
                        'RAW-TELUR-001' => ['quantity' => 2],
                        'RAW-SUSU-001' => ['quantity' => 200],
                        'RAW-SELAI-002' => ['quantity' => 60],
                    ],
                ],
                [
                    'sku' => 'FG-ROTIMANIS-001',
                    'name' => 'Roti Manis Isi Keju',
                    'category_id' => $catRoti->id,
                    'description' => 'Roti manis empuk dengan isian keju leleh',
                    'retail_price' => 7000,
                    'initial_stock' => 20,
                    'min_stock' => 5,
                    'expected_yield' => 8,
                    'recipe' => [
                        'RAW-TEPUNG-001' => ['quantity' => 0.500],
                        'RAW-GULA-001' => ['quantity' => 0.080],
                        'RAW-MARGARIN-001' => ['quantity' => 0.050],
                        'RAW-RAGI-001' => ['quantity' => 11],
                        'RAW-TELUR-001' => ['quantity' => 2],
                        'RAW-SUSU-001' => ['quantity' => 150],
                        'RAW-KEJU-001' => ['quantity' => 0.100],
                        'RAW-SUSU-002' => ['quantity' => 20],
                    ],
                ],
                [
                    'sku' => 'FG-ROTIMANIS-003',
                    'name' => 'Roti Manis Cokelat Premium',
                    'kategori' => 'Roti',
                    'description' => 'Roti manis empuk menggunakan adonan dasar',
                    'retail_price' => 8000,
                    'initial_stock' => 10,
                    'min_stock' => 5,
                    'expected_yield' => 10,
                    'recipe' => [
                        'INT-ADONAN-001' => ['quantity' => 500],
                        'RAW-SELAI-002' => ['quantity' => 100],
                    ],
                ],
                [
                    'sku' => 'FG-DONAT-001',
                    'name' => 'Donat Chocolate Glaze',
                    'category_id' => $catKue->id,
                    'description' => 'Donat empuk dengan lapisan cokelat glaze',
                    'retail_price' => 8500,
                    'initial_stock' => 24,
                    'min_stock' => 6,
                    'expected_yield' => 12,
                    'recipe' => [
                        'RAW-TEPUNG-002' => ['quantity' => 0.500],
                        'RAW-GULA-001' => ['quantity' => 0.100],
                        'RAW-MARGARIN-001' => ['quantity' => 0.050],
                        'RAW-RAGI-001' => ['quantity' => 8],
                        'RAW-TELUR-001' => ['quantity' => 2],
                        'RAW-SUSU-001' => ['quantity' => 150],
                        'RAW-COKELAT-001' => ['quantity' => 0.150],
                        'RAW-MINYAK-001' => ['quantity' => 500],
                        'RAW-VANILI-001' => ['quantity' => 2],
                    ],
                ],
            ];

            $unitPcsId = $unitPcs->id;
            $productionModels = [];

            // Merge and Create Products
            $allBOMProducts = array_merge($intermediateGoods, $finishedGoods);

            foreach ($allBOMProducts as $data) {
                $type = str_starts_with($data['sku'], 'FG-') ? 'finished_good' : 'intermediate_good';

                $product = Product::create([
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'kategori' => $data['kategori'],
                    'description' => $data['description'],
                    'unit_id' => $data['unit_id'] ?? $unitPcsId,
                    'type' => $type,
                    'track_stock' => true,
                    'is_active' => true,
                    'min_stock' => $data['min_stock'] ?? 0,
                ]);

                Price::create([
                    'product_id' => $product->id,
                    'unit_id' => $product->unit_id,
                    'purchase_price' => 0,
                    'retail_price' => $data['retail_price'] ?? 0,
                    'is_current' => true,
                ]);

                Stock::create([
                    'product_id' => $product->id,
                    'last_unit_id' => $product->unit_id,
                    'balance' => $data['initial_stock'] ?? 0,
                ]);

                $bom = Bom::create([
                    'product_id' => $product->id,
                    'sku' => 'BOM-'.$data['sku'],
                    'name' => 'Resep '.$data['name'],
                    'is_active' => true,
                    'expected_yield' => $data['expected_yield'],
                    'auto_deduct_on_sale' => true,
                ]);

                foreach ($data['recipe'] as $materialSku => $itemData) {
                    $material = $materialModels[$materialSku] ?? Product::where('sku', $materialSku)->first();
                    if ($material) {
                        BomItem::create([
                            'bom_id' => $bom->id,
                            'product_id' => $material->id,
                            'unit_id' => $material->unit_id,
                            'quantity' => $itemData['quantity'],
                        ]);
                    }
                }

                app(RecalculateHpp::class)->handle($product);
                $productionModels[$data['sku']] = ['product' => $product, 'bom' => $bom];
            }

            // ─── 7. Productions ──────────────────────────────────────────
            $pData = [
                [
                    'sku' => 'PRD-'.date('ym').'-0001',
                    'date' => now()->subDays(2),
                    'model' => $productionModels['FG-ROTITAWAR-001'],
                    'target_yield' => 20,
                    'actual_yield' => 20,
                    'status' => 'completed',
                ],
                [
                    'sku' => 'PRD-'.date('ym').'-0002',
                    'date' => now(),
                    'model' => $productionModels['FG-DONAT-001'],
                    'target_yield' => 48,
                    'actual_yield' => 0,
                    'status' => 'in_progress',
                ],
                [
                    'sku' => 'PRD-'.date('ym').'-0003',
                    'date' => now()->subDays(3),
                    'model' => $productionModels['INT-ADONAN-001'],
                    'target_yield' => 1000,
                    'actual_yield' => 1000,
                    'status' => 'completed',
                ],
                [
                    'sku' => 'PRD-'.date('ym').'-0004',
                    'date' => now()->subDays(1),
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
                    'date' => $pd['date'],
                    'bom_id' => $m['bom']->id,
                    'product_id' => $m['product']->id,
                    'target_yield' => $pd['target_yield'],
                    'actual_yield' => $pd['actual_yield'] ?: null,
                    'status' => $pd['status'],
                    'total_cost' => 0,
                ]);

                $costSum = 0;
                $scale = $pd['target_yield'] / $m['bom']->expected_yield;

                foreach ($m['bom']->items as $item) {
                    $itemPrice = $item->product->currentPrice->purchase_price ?? 0;
                    $qty = $item->quantity * $scale;

                    if ($pd['status'] === 'completed') {
                        $costSum += ($itemPrice * $qty);
                    }

                    ProductionItem::create([
                        'production_id' => $prod->id,
                        'product_id' => $item->product_id,
                        'unit_id' => $item->unit_id,
                        'planned_qty' => $qty,
                        'actual_qty' => ($pd['status'] === 'completed') ? $qty : 0,
                        'unit_price' => $itemPrice,
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
