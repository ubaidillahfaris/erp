<?php

namespace Database\Factories;

use App\Models\Bom;
use App\Models\Produk;
use App\Models\Satuan;
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
            'produk_id' => Produk::factory(),
            'sku' => 'BOM-'.$this->faker->unique()->numberBetween(1000, 9999),
            'nama' => $this->faker->words(3, true),
            'expected_yield' => $this->faker->numberBetween(10, 100),
            'yield_satuan_id' => Satuan::factory(),
        ];
    }
}
