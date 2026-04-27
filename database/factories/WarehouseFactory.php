<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Warehouse',
            'code' => strtoupper($this->faker->unique()->lexify('WH-???')),
            'address' => $this->faker->address(),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
