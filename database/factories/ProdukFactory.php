<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produk>
 */
class ProdukFactory extends Factory
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
            'nama' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'deskripsi' => fake()->sentence(),
            'stok_minimal' => fake()->numberBetween(5, 50),
            'is_active' => true,
        ];
    }
}
