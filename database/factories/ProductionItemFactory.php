<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionItem>
 */
class ProductionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_id' => Production::factory(),
            'product_id' => Product::factory(),
            'unit_id' => Unit::factory(),
            'planned_qty' => $this->faker->numberBetween(1, 50),
            'actual_qty' => $this->faker->numberBetween(1, 50),
            'unit_price' => $this->faker->numberBetween(1000, 10000),
        ];
    }
}
