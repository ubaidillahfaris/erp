<?php

namespace Database\Factories;

use App\Models\ServiceOrder;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_order_id' => ServiceOrder::factory(),
            'service_type_id' => ServiceType::factory(),
            'quantity' => 1.0,
            'unit_price' => 1000000,
            'discount_pct' => 0,
            'subtotal' => 1000000,
        ];
    }
}
