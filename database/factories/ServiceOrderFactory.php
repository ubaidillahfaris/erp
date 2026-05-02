<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'order_number' => $this->faker->unique()->numerify('ORD-######'),
            'service_id' => Service::factory(),
            'customer_type' => 'customer',
            'party_type' => Customer::class,
            'party_id' => Customer::factory(),
            'order_date' => now()->toDateString(),
            'production_step_id' => null,
            'total_amount' => 1000000,
            'total_paid' => 0,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }
}
