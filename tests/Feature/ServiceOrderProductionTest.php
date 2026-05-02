<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ProductionStep;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\Customer;
use App\Services\ServiceOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderProductionTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $user;
    protected $service;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
    }

    public function test_can_create_production_steps()
    {
        $step = ProductionStep::create([
            'company_id' => $this->company->id,
            'name' => 'Sortir',
            'code' => 'SORTIR',
            'sequence_order' => 1,
            'is_start' => true,
        ]);

        $this->assertDatabaseHas('production_steps', [
            'name' => 'Sortir',
            'code' => 'SORTIR',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_order_is_assigned_to_start_step_on_creation()
    {
        $startStep = ProductionStep::create([
            'company_id' => $this->company->id,
            'name' => 'Entry',
            'code' => 'ENTRY',
            'is_start' => true,
        ]);

        $order = ServiceOrder::create([
            'company_id' => $this->company->id,
            'service_id' => $this->service->id,
            'customer_type' => 'customer',
            'party_type' => Customer::class,
            'party_id' => $this->customer->id,
            'order_number' => 'TEST-001',
            'order_date' => now(),
            'production_step_id' => $startStep->id,
            'total_amount' => 1000,
            'status' => 'draft',
        ]);

        $this->assertEquals($startStep->id, $order->production_step_id);
    }

    public function test_can_transition_to_next_step()
    {
        $step1 = ProductionStep::create(['company_id' => $this->company->id, 'name' => 'A', 'code' => 'A', 'sequence_order' => 1]);
        $step2 = ProductionStep::create(['company_id' => $this->company->id, 'name' => 'B', 'code' => 'B', 'parent_step_id' => $step1->id, 'sequence_order' => 2]);
        
        $order = ServiceOrder::factory()->create([
            'company_id' => $this->company->id,
            'production_step_id' => $step1->id,
        ]);

        $service = app(ServiceOrderService::class);
        $service->updateProductionStep($order, $step2->id);

        $this->assertEquals($step2->id, $order->fresh()->production_step_id);
    }

    public function test_final_step_posts_order()
    {
        // Setup accounts for journaling
        \App\Models\Account::factory()->create(['code' => '4201']);
        \App\Models\Account::factory()->create(['code' => '6401']);
        \App\Models\Account::factory()->create(['code' => '1102']);
        \App\Models\Account::factory()->create(['code' => '2101']);
        \App\Models\Account::factory()->create(['code' => '1101']);

        $step1 = ProductionStep::create(['company_id' => $this->company->id, 'name' => 'A', 'code' => 'A']);
        $step2 = ProductionStep::create([
            'company_id' => $this->company->id, 
            'name' => 'Done', 
            'code' => 'DONE', 
            'is_final' => true, 
            'parent_step_id' => $step1->id
        ]);

        $order = ServiceOrder::factory()->create([
            'company_id' => $this->company->id,
            'production_step_id' => $step1->id,
            'status' => 'draft',
            'customer_type' => 'customer',
        ]);

        $service = app(ServiceOrderService::class);
        $service->updateProductionStep($order, $step2->id);

        $this->assertEquals('posted', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completion_date);
    }
}
