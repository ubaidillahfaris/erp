<?php

namespace Database\Factories;

use App\Models\Journal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Journal>
 */
class JournalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => $this->faker->date(),
            'type' => $this->faker->randomElement(['debit', 'kredit']),
            'amount' => $this->faker->randomFloat(2, 1000, 100000),
            'category' => $this->faker->word(),
            'payment_method' => $this->faker->randomElement(['cash', 'transfer', 'qris']),
            'description' => $this->faker->sentence(),
        ];
    }
}
