<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'barcode' => fake()->ean13(),
            'name' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'description' => fake()->sentence(),
            'min_stock' => fake()->numberBetween(5, 50),
            'is_active' => true,
        ];
    }
}
