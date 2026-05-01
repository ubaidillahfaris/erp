<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => $this->faker->unique()->lexify('SVC-???'),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'service_category' => 'laundry',
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
