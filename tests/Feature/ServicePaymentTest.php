<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    /** @test */
    public function test_can_record_multiple_payments()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('superadmin');
        $this->actingAs($user);

        $order = ServiceOrder::factory()->create([
            'company_id' => $company->id,
            'total_amount' => 1000000,
            'total_paid' => 0
        ]);

        $this->post(route('service-orders.record-payment', $order), [
            'amount' => 3000,
            'payment_method' => 'cash'
        ]);

        $this->post(route('service-orders.record-payment', $order), [
            'amount' => 7000,
            'payment_method' => 'bank_transfer'
        ]);

        $this->assertEquals(1000000, $order->fresh()->total_paid);
        $this->assertCount(2, $order->payments);
    }
}
