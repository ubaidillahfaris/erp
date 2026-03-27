<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Produk>
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
            'kategori' => fake()->randomElement(['Makanan', 'Minuman', 'Sembako']),
            'deskripsi' => fake()->sentence(),
            'stok_minimal' => fake()->numberBetween(5, 50),
            'is_active' => true,
        ];
    }
}
