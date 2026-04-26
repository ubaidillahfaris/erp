<?php

namespace Database\Factories;

use App\Models\Production;
use App\Models\ProductionItem;
use App\Models\Produk;
use App\Models\Satuan;
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
            'produk_id' => Produk::factory(),
            'satuan_id' => Satuan::factory(),
            'planned_qty' => $this->faker->numberBetween(1, 50),
            'actual_qty' => $this->faker->numberBetween(1, 50),
            'harga_satuan' => $this->faker->numberBetween(1000, 10000),
        ];
    }
}
