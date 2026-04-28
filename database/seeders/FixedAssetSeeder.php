<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\FixedAsset;
use App\Models\User;
use App\Services\DepreciationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixedAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = app(DepreciationService::class);
        $user = User::first() ?? User::factory()->create();

        // Truncate existing data to avoid duplicates
        // Note: Using CASCADE for PostgreSQL
        DB::statement('TRUNCATE fixed_assets RESTART IDENTITY CASCADE');

        $assetAcc = Account::where('code', '1401')->first();
        $accumAcc = Account::where('code', '1499')->first();
        $expenseAcc = Account::where('code', '6301')->first();

        $assets = [
            [
                'name' => 'Showcase Cooler Polytron',
                'category' => 'Peralatan',
                'acquisition_cost' => 450000000,
                'useful_life_months' => 48,
            ],
            [
                'name' => 'Deep Fryer Gas Butterfly',
                'category' => 'Mesin',
                'acquisition_cost' => 320000000,
                'useful_life_months' => 36,
            ],
            [
                'name' => 'Honda Vario 160 (Delivery)',
                'category' => 'Kendaraan',
                'acquisition_cost' => 2850000000,
                'useful_life_months' => 60,
            ],
            [
                'name' => 'MacBook Air M2 (Admin)',
                'category' => 'Peralatan',
                'acquisition_cost' => 1550000000,
                'useful_life_months' => 48,
            ],
            [
                'name' => 'AC Split Daikin 1 PK',
                'category' => 'Peralatan',
                'acquisition_cost' => 520000000,
                'useful_life_months' => 48,
            ],
        ];

        foreach ($assets as $data) {
            $asset = FixedAsset::create(array_merge($data, [
                'description' => 'Aset operasional warung',
                'acquisition_date' => now()->subMonths(rand(1, 12))->format('Y-m-d'),
                'salvage_value' => 0,
                'current_book_value' => $data['acquisition_cost'],
                'status' => 'active',
                'asset_account_id' => $assetAcc->id,
                'depreciation_account_id' => $accumAcc->id,
                'expense_account_id' => $expenseAcc->id,
                'created_by' => $user->id,
            ]));

            $service->generateSchedule($asset);
        }

        // Contoh aset yang sudah dihentikan
        $oldAsset = FixedAsset::create([
            'name' => 'Timbangan Digital (Lama)',
            'category' => 'Peralatan',
            'acquisition_date' => '2023-01-01',
            'acquisition_cost' => 85000000,
            'useful_life_months' => 12,
            'salvage_value' => 0,
            'current_book_value' => 0,
            'status' => 'disposed',
            'asset_account_id' => $assetAcc->id,
            'depreciation_account_id' => $accumAcc->id,
            'expense_account_id' => $expenseAcc->id,
            'created_by' => $user->id,
            'description' => 'Sudah rusak dan diganti',
        ]);
        $service->generateSchedule($oldAsset);
        $oldAsset->schedules()->where('status', 'scheduled')->delete();
    }
}
