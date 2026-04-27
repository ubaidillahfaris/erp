<?php

namespace Database\Factories;

use App\Models\Bom;
use App\Models\Product;
use App\Models\Production;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Production>
 */
class ProductionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => 'PROD-'.$this->faker->unique()->numberBetween(1000, 9999),
            'product_id' => Product::factory(),
            'bom_id' => Bom::factory(),
            'date' => now(),
            'target_yield' => $this->faker->numberBetween(10, 100),
            'status' => 'pending',
        ];
    }
}
