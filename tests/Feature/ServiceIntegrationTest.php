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

class ServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    /** @test */
    public function test_full_service_lifecycle_from_create_to_void()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('superadmin');
        $this->actingAs($user);

        // 1. Setup Service
        $svc = Service::factory()->create(['company_id' => $company->id]);
        $type = ServiceType::factory()->create(['service_id' => $svc->id]);
        ServicePricing::factory()->create(['service_type_id' => $type->id, 'unit_price' => 100000]);
        
        ServiceProcessingStatus::factory()->create([
            'service_id' => $svc->id,
            'status_code' => 'pending',
            'is_default_start' => true,
            'is_final' => false,
        ]);

        ServiceProcessingStatus::factory()->create([
            'service_id' => $svc->id,
            'status_code' => 'completed',
            'is_default_start' => false,
            'is_final' => true,
        ]);
        
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        // 2. Create Order
        $this->post(route('service-orders.store'), [
            'service_id' => $svc->id,
            'customer_type' => 'customer',
            'party_id' => $customer->id,
            'items' => [['service_type_id' => $type->id, 'quantity' => 1]]
        ]);
        $order = \App\Models\ServiceOrder::latest()->first();
        $this->assertEquals(100000, $order->total_amount);

        // 3. Complete Order
        $this->patch(route('service-orders.update-status', $order), ['status_code' => 'completed']);
        $order = $order->fresh();
        $this->assertEquals('posted', $order->status);
        $this->assertNotNull($order->journal_entry_id);

        // 4. Void Order
        $this->post(route('service-orders.void', $order), ['reason' => 'Customer changed mind']);
        $this->assertEquals('cancelled', $order->fresh()->status);
    }
}
