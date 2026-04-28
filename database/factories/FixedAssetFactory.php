<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedAsset>
 */
class FixedAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = $this->faker->numberBetween(5000000, 50000000); // 5jt - 50jt in cents

        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['Peralatan', 'Mesin', 'Kendaraan']),
            'acquisition_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'acquisition_cost' => $cost,
            'useful_life_months' => $this->faker->randomElement([12, 24, 36, 48]),
            'salvage_value' => 0,
            'current_book_value' => $cost,
            'status' => 'active',
            'asset_account_id' => Account::where('code', '1401')->first()?->id ?? 1,
            'depreciation_account_id' => Account::where('code', '1499')->first()?->id ?? 2,
            'expense_account_id' => Account::where('code', '6301')->first()?->id ?? 3,
            'created_by' => User::first()?->id ?? 1,
        ];
    }
}
