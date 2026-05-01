<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\ServicePricing;
use App\Models\ServiceProcessingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    /** @test */
    public function test_pricing_auto_calculation_in_store()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('superadmin');
        $this->actingAs($user);

        $svc = Service::factory()->create(['company_id' => $company->id]);
        $type = ServiceType::factory()->create(['service_id' => $svc->id]);
        
        ServicePricing::factory()->create([
            'service_type_id' => $type->id,
            'unit_price' => 1000000, // 10000
            'min_quantity' => 0,
        ]);
        
        ServiceProcessingStatus::factory()->create([
            'service_id' => $svc->id,
            'status_code' => 'pending',
            'is_default_start' => true,
        ]);

        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $this->post(route('service-orders.store'), [
            'service_id' => $svc->id,
            'customer_type' => 'customer',
            'party_id' => $customer->id,
            'items' => [['service_type_id' => $type->id, 'quantity' => 2.5]]
        ]);

        $order = \App\Models\ServiceOrder::latest()->first();
        // 2.5 * 10000 = 25000. In cents: 2,500,000
        $this->assertEquals(2500000, $order->total_amount);
    }
}
