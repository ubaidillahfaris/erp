<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\ServiceProcessingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    /** @test */
    public function test_cannot_transition_from_final_status()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('superadmin');
        $this->actingAs($user);

        $svc = Service::factory()->create(['company_id' => $company->id]);
        
        ServiceProcessingStatus::factory()->create([
            'service_id' => $svc->id,
            'status_code' => 'completed',
            'is_final' => true,
        ]);

        ServiceProcessingStatus::factory()->create([
            'service_id' => $svc->id,
            'status_code' => 'other',
            'is_final' => false,
        ]);
        
        $order = ServiceOrder::factory()->create([
            'company_id' => $company->id,
            'service_id' => $svc->id,
            'current_status_code' => 'completed'
        ]);

        $this->withoutExceptionHandling();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Cannot transition from a final status.");

        $this->patch(route('service-orders.update-status', $order), [
            'status_code' => 'other'
        ]);
    }
}
