<?php

namespace Database\Factories;

use App\Models\Bom;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bom>
 */
class BomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => 'BOM-'.$this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->words(3, true),
            'expected_yield' => $this->faker->numberBetween(10, 100),
            'yield_unit_id' => Unit::factory(),
        ];
    }
}
