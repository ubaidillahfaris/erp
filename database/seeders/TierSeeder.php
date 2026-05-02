<?php

namespace Database\Seeders;

use App\Models\Tier;
use App\Models\TierFeature;
use Illuminate\Database\Seeder;

class TierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            'basic' => [
                'name' => 'Basic',
                'features' => [
                    'pos.basic',
                    'inventory.view',
                    'reports.basic',
                ],
            ],
            'pro' => [
                'name' => 'Pro',
                'features' => [
                    'pos.advanced',
                    'inventory.full',
                    'reports.advanced',
                    'reports.export_pdf',
                    'crm.basic',
                ],
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'features' => [
                    'pos.full',
                    'inventory.multi_warehouse',
                    'reports.full',
                    'reports.export_pdf',
                    'reports.bulk_delete',
                    'crm.advanced',
                    'accounting.full',
                ],
            ],
        ];

        $moduleMap = \App\Models\Module::pluck('id', 'slug')->toArray();

        foreach ($tiers as $slug => $data) {
            $tier = Tier::updateOrCreate(
                ['slug' => $slug],
                ['name' => $data['name']]
            );

            // Sync features
            $tier->features()->delete();
            foreach ($data['features'] as $featureKey) {
                // Determine module slug from feature key (e.g., "pos.basic" -> "pos")
                $moduleSlug = explode('.', $featureKey)[0];
                
                TierFeature::create([
                    'tier_id' => $tier->id,
                    'feature_key' => $featureKey,
                    'module_id' => $moduleMap[$moduleSlug] ?? null,
                ]);
            }
        }
    }
}
