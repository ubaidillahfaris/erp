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

class MaterialAndPlasticSeeder extends Seeder
{
    /**
     * Seed realistic Building Materials and Plastic Manufacturing data.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // ─── 1. Units (Unit) ───────────────────────────────────
            $unitKg = Unit::where('symbol', 'kg')->first();
            $unitGr = Unit::where('symbol', 'gr')->first();
            $unitPcs = Unit::where('symbol', 'pcs')->first();
            $unitL = Unit::where('symbol', 'L')->first();

            $unitSak = Unit::firstOrCreate(
                ['symbol' => 'sak'],
                ['name' => 'Sak', 'description' => 'Unit untuk semen/berat 40-50kg'],
            );

            $unitM3 = Unit::firstOrCreate(
                ['symbol' => 'm3'],
                ['name' => 'Meter Kubik', 'description' => 'Unit volume material urug'],
            );

            $unitLonjor = Unit::firstOrCreate(
                ['symbol' => 'lon'],
                ['name' => 'Lonjor', 'description' => 'Unit untuk besi beton/pipa'],
            );

            $unitGalon = Unit::firstOrCreate(
                ['symbol' => 'gal'],
                ['name' => 'Galon', 'description' => 'Unit untuk cat (biasanya 5kg/2.5L)'],
            );

            $unitRoll = Unit::firstOrCreate(
                ['symbol' => 'roll'],
                ['name' => 'Roll', 'description' => 'Unit untuk plastik roll/product panjang'],
            );

            $unitPack = Unit::firstOrCreate(
                ['symbol' => 'pack'],
                ['name' => 'Pack', 'description' => 'Unit kemasan isi banyak'],
            );

            // ─── 2. Vendors (Suppliers) ──────────────────────────────
            $vendorSemen = Vendor::create([
                'name' => 'PT Semen Indonesia (Persero) Tbk',
                'address' => 'Gresik, Jawa Timur',
                'phone' => '031-3981732',
                'email' => 'sales@semenindonesia.com',
                'notes' => 'Distributor utama Semen Tiga Roda dan Holcim',
            ]);

            $vendorMaterial = Vendor::create([
                'name' => 'UD Pasir Berkah Alam',
                'address' => 'Jl. Raya Muntilan, Magelang',
                'phone' => '0812-9988-7766',
                'email' => 'muntilanpasir@gmail.com',
                'notes' => 'Supplier pasir merapi dan batu kali',
            ]);

            $vendorPlastik = Vendor::create([
                'name' => 'PT Global Polimer Indonesia',
                'address' => 'Kawasan Industri Jababeka, Cikarang',
                'phone' => '021-8931234',
                'email' => 'info@globalpolimer.co.id',
                'notes' => 'Importir biji plastik HDPE, LDPE, PP',
            ]);

            // ─── 3. Categories ──────────────────────────────────────
            $catMaterial = Category::firstOrCreate(['name' => 'Material Bangunan'], ['slug' => 'material-bangunan']);
            $catCatFinishing = Category::firstOrCreate(['name' => 'Cat & Finishing'], ['slug' => 'cat-finishing']);
            $catBahanBakuPlastik = Category::firstOrCreate(['name' => 'Raw Materials Plastik'], ['slug' => 'bahan-baku-plastik']);
            $catBahanPenolong = Category::firstOrCreate(['name' => 'Bahan Penolong'], ['slug' => 'bahan-penolong']);
            $catProductPlastik = Category::firstOrCreate(['name' => 'Product Plastik'], ['slug' => 'product-plastik']);

            // ─── 4. Products — Raw Materials & Retail Items ─────────
            $products = [
                // Building Materials (Retail & Stockable)
                [
                    'sku' => 'MAT-SEMEN-001',
                    'name' => 'Semen Tiga Roda 40kg',
                    'category_id' => $catMaterial->id,
                    'description' => 'Semen Portland berkualitas tinggi untuk konstruksi',
                    'unit' => $unitSak,
                    'purchase_price' => 58000,
                    'retail_price' => 65000,
                    'initial_stock' => 100,
                    'min_stock' => 20,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'MAT-PASIR-001',
                    'name' => 'Pasir Muntilan (Super)',
                    'category_id' => $catMaterial->id,
                    'description' => 'Pasir hitam vulkanik dari lereng Merapi',
                    'unit' => $unitM3,
                    'purchase_price' => 220000,
                    'retail_price' => 280000,
                    'initial_stock' => 50,
                    'min_stock' => 10,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'MAT-BESI-001',
                    'name' => 'Besi Beton 8mm (Polos)',
                    'category_id' => $catMaterial->id,
                    'description' => 'Besi beton standar SNI, panjang 12m',
                    'unit' => $unitLonjor,
                    'purchase_price' => 45000,
                    'retail_price' => 52000,
                    'initial_stock' => 200,
                    'min_stock' => 50,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'MAT-CAT-001',
                    'name' => 'Avian Emulsion White 5kg',
                    'category_id' => $catCatFinishing->id,
                    'description' => 'Cat tembok interior warna putih bersih',
                    'unit' => $unitGalon,
                    'purchase_price' => 125000,
                    'retail_price' => 145000,
                    'initial_stock' => 30,
                    'min_stock' => 5,
                    'type' => 'raw_material',
                ],

                // Plastic Raw Materials
                [
                    'sku' => 'RAW-PLAS-HDPE',
                    'name' => 'Biji Plastik HDPE Virgin',
                    'category_id' => $catBahanBakuPlastik->id,
                    'description' => 'High Density Polyethylene untuk kantong kresek',
                    'unit' => $unitKg,
                    'purchase_price' => 18500,
                    'retail_price' => 0,
                    'initial_stock' => 1000,
                    'min_stock' => 200,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'RAW-PLAS-LDPE',
                    'name' => 'Biji Plastik LDPE Virgin',
                    'category_id' => $catBahanBakuPlastik->id,
                    'description' => 'Low Density Polyethylene untuk botol dan plastik lentur',
                    'unit' => $unitKg,
                    'purchase_price' => 19500,
                    'retail_price' => 0,
                    'initial_stock' => 500,
                    'min_stock' => 100,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'RAW-PIGM-WHT',
                    'name' => 'Pigmen Putih (Titanium Dioxide)',
                    'category_id' => $catBahanPenolong->id,
                    'description' => 'Pewarna putih untuk product plastik',
                    'unit' => $unitGr,
                    'purchase_price' => 150, // per gram
                    'retail_price' => 0,
                    'initial_stock' => 5000,
                    'min_stock' => 1000,
                    'type' => 'raw_material',
                ],
                [
                    'sku' => 'RAW-PIGM-BLK',
                    'name' => 'Pigmen Hitam (Carbon Black)',
                    'category_id' => $catBahanPenolong->id,
                    'description' => 'Pewarna hitam untuk product plastik',
                    'unit' => $unitGr,
                    'purchase_price' => 120, // per gram
                    'retail_price' => 0,
                    'initial_stock' => 3000,
                    'min_stock' => 500,
                    'type' => 'raw_material',
                ],
            ];

            $materialModels = [];
            foreach ($products as $data) {
                $product = Product::create([
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'category_id' => $data['category_id'],
                    'description' => $data['description'],
                    'unit_id' => $data['unit']->id,
                    'type' => $data['type'],
                    'track_stock' => true,
                    'is_active' => true,
                    'min_stock' => $data['min_stock'],
                    'overhead_rate_per_unit' => 0,
                ]);

                Price::create([
                    'product_id' => $product->id,
                    'unit_id' => $data['unit']->id,
                    'purchase_price' => $data['purchase_price'],
                    'retail_price' => $data['retail_price'],
                    'is_current' => true,
                ]);

                Stock::create([
                    'product_id' => $product->id,
                    'last_unit_id' => $data['unit']->id,
                    'balance' => $data['initial_stock'],
                ]);

                $materialModels[$data['sku']] = $product;
            }

            // ─── 4. Products — Finished Goods (Plastic) ──────────
            $finishedGoods = [
                [
                    'sku' => 'FG-PLAS-WHT24',
                    'name' => 'Kantong Kresek HD Putih Uk. 24',
                    'category_id' => $catProductPlastik->id,
                    'description' => 'Kantong plastik putih ukuran 24 (isi ±50 lembar)',
                    'retail_price' => 12500,
                    'initial_stock' => 50,
                    'min_stock' => 20,
                    'unit_id' => $unitPack->id,
                    'expected_yield' => 1,
                    'overhead_rate_per_unit' => 500,
                    'recipe' => [
                        'RAW-PLAS-HDPE' => ['quantity' => 0.480], // 480 grams
                        'RAW-PIGM-WHT' => ['quantity' => 20],   // 20 grams
                    ],
                ],
                [
                    'sku' => 'FG-PLAS-BLK24',
                    'name' => 'Kantong Kresek HD Hitam Uk. 24',
                    'category_id' => $catProductPlastik->id,
                    'description' => 'Kantong plastik hitam ukuran 24 (isi ±50 lembar)',
                    'retail_price' => 10500,
                    'initial_stock' => 80,
                    'min_stock' => 20,
                    'unit_id' => $unitPack->id,
                    'expected_yield' => 1,
                    'overhead_rate_per_unit' => 500,
                    'recipe' => [
                        'RAW-PLAS-HDPE' => ['quantity' => 0.490], // 490 grams
                        'RAW-PIGM-BLK' => ['quantity' => 10],   // 10 grams
                    ],
                ],
                [
                    'sku' => 'FG-BOTL-600',
                    'name' => 'Botol Plastik PET 600ml',
                    'category_id' => $catProductPlastik->id,
                    'description' => 'Botol transparan polos 600ml untuk minuman',
                    'retail_price' => 1200,
                    'initial_stock' => 1000,
                    'min_stock' => 500,
                    'unit_id' => $unitPcs->id,
                    'expected_yield' => 100, // Produced in batches of 100
                    'overhead_rate_per_unit' => 50,
                    'recipe' => [
                        'RAW-PLAS-LDPE' => ['quantity' => 3.0], // 3kg for 100 bottles -> 30g/bottle
                    ],
                ],
            ];

            $productionModels = [];

            foreach ($finishedGoods as $data) {
                $product = Product::create([
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'category_id' => $data['category_id'],
                    'description' => $data['description'],
                    'unit_id' => $data['unit_id'],
                    'type' => 'finished_good',
                    'track_stock' => true,
                    'is_active' => true,
                    'min_stock' => $data['min_stock'],
                    'overhead_rate_per_unit' => $data['overhead_rate_per_unit'] ?? 0,
                ]);

                Price::create([
                    'product_id' => $product->id,
                    'unit_id' => $product->unit_id,
                    'purchase_price' => 0,
                    'retail_price' => $data['retail_price'],
                    'is_current' => true,
                ]);

                Stock::create([
                    'product_id' => $product->id,
                    'last_unit_id' => $product->unit_id,
                    'balance' => $data['initial_stock'],
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

                // Recalculate HPP based on ingredients
                app(RecalculateHpp::class)->handle($product);
                $productionModels[$data['sku']] = ['product' => $product, 'bom' => $bom];
            }

            // ─── 5. Initial Productions (Optional History) ──────────
            $pData = [
                [
                    'sku' => 'PROD-PLAS-'.date('ym').'-001',
                    'date' => now()->subDays(1),
                    'model' => $productionModels['FG-PLAS-WHT24'],
                    'target_yield' => 100,
                    'actual_yield' => 100,
                    'status' => 'completed',
                ],
                [
                    'sku' => 'PROD-BOTL-'.date('ym').'-001',
                    'date' => now(),
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
                    'date' => $pd['date'],
                    'bom_id' => $m['bom']->id,
                    'product_id' => $m['product']->id,
                    'target_yield' => $pd['target_yield'],
                    'actual_yield' => $pd['actual_yield'] ?: null,
                    'status' => $pd['status'],
                    'total_cost' => 0,
                ]);

                $scale = $pd['target_yield'] / $m['bom']->expected_yield;
                $costSum = 0;

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
