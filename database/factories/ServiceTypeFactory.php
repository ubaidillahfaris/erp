<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'code' => $this->faker->unique()->lexify('TYPE-???'),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
