<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceProcessingStatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'status_code' => 'pending',
            'status_name' => 'Pending',
            'sequence_order' => 1,
            'is_default_start' => true,
            'is_final' => false,
        ];
    }
}
