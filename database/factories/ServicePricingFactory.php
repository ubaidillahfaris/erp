<?php

namespace Database\Factories;

use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServicePricingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_type_id' => ServiceType::factory(),
            'pricing_basis' => 'per_kg',
            'unit_name' => 'kg',
            'unit_price' => 500000, // 5000.00
            'min_quantity' => 0,
            'max_quantity' => null,
            'discount_pct' => 0,
            'is_active' => true,
        ];
    }
}
