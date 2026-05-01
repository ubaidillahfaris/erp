<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceOrderPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'service_order_id' => ServiceOrder::factory(),
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount' => 1000000,
            'created_by' => User::factory(),
        ];
    }
}
