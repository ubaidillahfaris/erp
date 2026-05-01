<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\ServicePricing;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole('superadmin');
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function test_user_can_view_service_order_list()
    {
        $response = $this->get(route('service-orders.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function test_user_can_create_service_order()
    {
        $svc = Service::factory()->create(['company_id' => $this->company->id]);
        $type = ServiceType::factory()->create(['service_id' => $svc->id]);
        ServicePricing::factory()->create(['service_type_id' => $type->id]);
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post(route('service-orders.store'), [
            'service_id' => $svc->id,
            'customer_type' => 'customer',
            'party_id' => $customer->id,
            'items' => [
                ['service_type_id' => $type->id, 'quantity' => 5]
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('service_orders', [
            'service_id' => $svc->id,
            'party_id' => $customer->id,
        ]);
    }

    /** @test */
    public function test_user_can_view_service_order_details()
    {
        $order = ServiceOrder::factory()->create(['company_id' => $this->company->id]);
        
        $response = $this->get(route('service-orders.show', $order));
        $response->assertStatus(200);
    }

    /** @test */
    public function test_user_can_update_order_status()
    {
        $svc = Service::factory()->create(['company_id' => $this->company->id]);
        $status = \App\Models\ServiceProcessingStatus::factory()->create([
            'service_id' => $svc->id,
            'status_code' => 'in_progress'
        ]);
        $order = ServiceOrder::factory()->create([
            'company_id' => $this->company->id,
            'service_id' => $svc->id,
            'current_status_code' => 'pending'
        ]);

        $response = $this->patch(route('service-orders.update-status', $order), [
            'status_code' => 'in_progress'
        ]);

        $response->assertRedirect();
        $this->assertEquals('in_progress', $order->fresh()->current_status_code);
    }

    /** @test */
    public function test_user_can_record_payment()
    {
        $order = ServiceOrder::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 1000000,
            'total_paid' => 0
        ]);

        $response = $this->post(route('service-orders.record-payment', $order), [
            'amount' => 5000, // 5000 in UI unit
            'payment_method' => 'cash'
        ]);

        $response->assertRedirect();
        $this->assertEquals(500000, $order->fresh()->total_paid);
    }

    /** @test */
    public function test_user_can_void_service_order()
    {
        $order = ServiceOrder::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post(route('service-orders.void', $order), [
            'reason' => 'Duplicate order'
        ]);

        $response->assertRedirect();
        $this->assertEquals('cancelled', $order->fresh()->status);
    }
}
