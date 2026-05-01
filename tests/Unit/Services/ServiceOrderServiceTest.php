<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceProcessingStatus;
use App\Models\ServiceType;
use App\Models\ServicePricing;
use App\Models\ServiceOrder;
use App\Services\ServiceOrderService;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ServiceOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ServiceOrderService::class);
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    /** @test */
    public function test_it_calculates_price_for_per_kg_pricing()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $svc = Service::factory()->create(['company_id' => $company->id]);
        $type = ServiceType::factory()->create(['service_id' => $svc->id]);
        
        ServicePricing::factory()->create([
            'service_type_id' => $type->id,
            'pricing_basis' => 'per_kg',
            'unit_price' => 500000, // 5000
        ]);

        $order = $this->service->createOrder($svc->id, $customer, [
            ['service_type_id' => $type->id, 'quantity' => 10]
        ], 'customer');

        $this->assertEquals(5000000, $order->total_amount); // 5000 * 10 = 50000
    }

    /** @test */
    public function test_it_applies_tier_pricing_correctly()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $svc = Service::factory()->create(['company_id' => $company->id]);
        $type = ServiceType::factory()->create(['service_id' => $svc->id]);

        // 0-50kg: 5000/kg
        ServicePricing::factory()->create([
            'service_type_id' => $type->id,
            'min_quantity' => 0,
            'max_quantity' => 50,
            'unit_price' => 500000,
        ]);
        // 51-100kg: 4500/kg
        ServicePricing::factory()->create([
            'service_type_id' => $type->id,
            'min_quantity' => 51,
            'max_quantity' => 100,
            'unit_price' => 450000,
        ]);

        // Test bracket 1: 25kg
        $order1 = $this->service->createOrder($svc->id, $customer, [
            ['service_type_id' => $type->id, 'quantity' => 25]
        ], 'customer');
        $this->assertEquals(25 * 500000, $order1->total_amount);

        // Test bracket 2: 75kg
        $order2 = $this->service->createOrder($svc->id, $customer, [
            ['service_type_id' => $type->id, 'quantity' => 75]
        ], 'customer');
        $this->assertEquals(75 * 450000, $order2->total_amount);
    }

    /** @test */
    public function test_it_applies_pricing_discount()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $svc = Service::factory()->create(['company_id' => $company->id]);
        $type = ServiceType::factory()->create(['service_id' => $svc->id]);

        ServicePricing::factory()->create([
            'service_type_id' => $type->id,
            'unit_price' => 500000,
            'discount_pct' => 10,
        ]);

        $order = $this->service->createOrder($svc->id, $customer, [
            ['service_type_id' => $type->id, 'quantity' => 100]
        ], 'customer');

        // 100 * 5000 = 500,000. 10% discount = 450,000. In cents: 45,000,000
        $this->assertEquals(45000000, $order->total_amount);
    }

    /** @test */
    public function test_it_allows_valid_status_transition()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $svc = Service::factory()->create(['company_id' => $company->id]);
        
        ServiceProcessingStatus::factory()->create([
            'service_id' => $svc->id,
            'status_code' => 'pending',
            'is_default_start' => true,
            'is_final' => false,
            'sequence_order' => 1,
        ]);
        ServiceProcessingStatus::factory()->create([
            'service_id' => $svc->id,
            'status_code' => 'in_progress',
            'is_default_start' => false,
            'is_final' => false,
            'sequence_order' => 2,
        ]);

        $order = $this->service->createOrder($svc->id, $customer, [], 'customer');
        $this->assertEquals('pending', $order->current_status_code);

        $this->service->updateStatus($order, 'in_progress');
        $this->assertEquals('in_progress', $order->fresh()->current_status_code);
    }

    /** @test */
    public function test_it_posts_journal_when_reaching_final_status()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
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

        $order = $this->service->createOrder($svc->id, $customer, [
            ['service_type_id' => $type->id, 'quantity' => 1]
        ], 'customer');

        $this->service->updateStatus($order, 'completed');

        $order->refresh();
        $this->assertEquals('posted', $order->status);
        $this->assertNotNull($order->journal_entry_id);
        
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $order->journal_entry_id,
            'account_id' => Account::where('code', '4201')->first()->id,
            'credit' => 100000,
        ]);
    }

    /** @test */
    public function test_it_records_full_payment()
    {
        $company = Company::factory()->create();
        $order = ServiceOrder::factory()->create([
            'company_id' => $company->id,
            'total_amount' => 1000000,
            'total_paid' => 0
        ]);

        $this->service->recordPayment($order, 1000000, 'cash');

        $order->refresh();
        $this->assertEquals(1000000, $order->total_paid);
    }

    /** @test */
    public function test_it_posts_reversing_journal_on_void()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
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

        $order = $this->service->createOrder($svc->id, $customer, [['service_type_id' => $type->id, 'quantity' => 1]], 'customer');
        $this->service->updateStatus($order, 'completed');
        $order->refresh();
        
        $originalEntryId = $order->journal_entry_id;

        $this->service->void($order, 'Mistake');

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
        
        // Check for reversing journal (should be a new entry)
        $this->assertDatabaseHas('journal_entries', [
            'journalable_id' => $order->id,
            'journalable_type' => ServiceOrder::class,
            'description' => 'VOID: #'.$order->order_number.' Mistake',
        ]);
    }
}
