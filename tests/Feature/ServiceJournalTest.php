<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceProcessingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceJournalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    /** @test */
    public function test_journal_created_on_service_completion()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('superadmin');
        $this->actingAs($user);

        $svc = Service::factory()->create(['company_id' => $company->id]);
        
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

        $order = ServiceOrder::factory()->create([
            'company_id' => $company->id,
            'service_id' => $svc->id,
            'current_status_code' => 'pending',
            'total_amount' => 1000000
        ]);

        $this->patch(route('service-orders.update-status', $order), [
            'status_code' => 'completed'
        ]);

        $this->assertNotNull($order->fresh()->journal_entry_id);
    }
}
