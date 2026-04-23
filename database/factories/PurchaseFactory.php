<?php

namespace Database\Factories;

use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'no_invoice' => fake()->bothify('INV-####-??'),
            'vendor_id' => null,
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'transaction_type' => 'purchase',
            'status' => 'draft',
            'total_biaya' => fake()->randomFloat(2, 10000, 5000000),
            'keterangan' => fake()->optional()->sentence(),
            'signature_log' => null,
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'finalized',
            'signature_log' => [
                'user_id' => 1,
                'user_name' => 'System',
                'ip_address' => '127.0.0.1',
                'finalized_at' => now()->toISOString(),
            ],
        ]);
    }

    public function gift(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => 'gift',
            'vendor_id' => null,
        ]);
    }
}
