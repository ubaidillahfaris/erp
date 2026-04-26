<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ref_number' => 'JE-'.$this->faker->unique()->numberBetween(1000, 9999),
            'tanggal' => now(),
            'description' => $this->faker->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
