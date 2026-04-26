<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerStatus;
use App\Models\CustomerType;
use App\Models\Payable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class PayableControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->superadmin()->create();
        $this->actingAs($this->user);

        // Setup Customer
        $status = CustomerStatus::create(['name' => 'Active']);
        $type = CustomerType::create(['name' => 'Regular']);
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'customer_status_id' => $status->id,
            'customer_type_id' => $type->id,
        ]);
    }

    public function test_index_displays_payables_list_and_summary()
    {
        Payable::create([
            'type' => 'receivable',
            'party_type' => 'customer',
            'party_id' => $this->customer->id,
            'principal_amount' => 100000,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $this->get(route('payables.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payables/Index')
                ->has('payables.data', 1)
                ->where('summary.total_receivable', 100000)
            );
    }

    public function test_show_displays_payable_details()
    {
        $payable = Payable::create([
            'type' => 'receivable',
            'party_type' => 'customer',
            'party_id' => $this->customer->id,
            'principal_amount' => 100000,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $this->get(route('payables.show', $payable))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payables/Show')
                ->where('payable.total_amount', "100000.00")
                ->where('party.name', 'Test Customer')
            );
    }

    public function test_store_payment_updates_payable_balance()
    {
        $payable = Payable::create([
            'type' => 'receivable',
            'party_type' => 'customer',
            'party_id' => $this->customer->id,
            'principal_amount' => 100000,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $payload = [
            'amount' => 40000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'notes' => 'Partial payment',
        ];

        $this->post(route('payables.payments.store', $payable), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $payable->refresh();
        $this->assertEquals(40000, (float) $payable->paid_amount);
        $this->assertEquals(60000, (float) $payable->remaining_amount);
        $this->assertEquals('partial', $payable->status);
    }

    public function test_store_full_payment_updates_status_to_paid()
    {
        $payable = Payable::create([
            'type' => 'receivable',
            'party_type' => 'customer',
            'party_id' => $this->customer->id,
            'principal_amount' => 100000,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $this->post(route('payables.payments.store', $payable), [
            'amount' => 100000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
        ]);

        $payable->refresh();
        $this->assertEquals('paid', $payable->status);
        $this->assertEquals(0, (float) $payable->remaining_amount);
    }
    public function test_index_with_filters_and_sorting()
    {
        Payable::create([
            'type' => 'payable',
            'party_type' => 'customer',
            'party_id' => $this->customer->id,
            'principal_amount' => 50000,
            'total_amount' => 50000,
            'paid_amount' => 0,
            'remaining_amount' => 50000,
            'status' => 'overdue',
            'created_by' => $this->user->id,
            'notes' => 'UniqueNote',
        ]);

        $this->get(route('payables.index', ['search' => 'UniqueNote', 'status' => 'overdue', 'type' => 'payable']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('payables.data', 1)
                ->where('summary.overdue_count', 1)
            );
    }

    public function test_store_payment_validates_max_amount()
    {
        $payable = Payable::create([
            'type' => 'receivable',
            'party_type' => 'customer',
            'party_id' => $this->customer->id,
            'principal_amount' => 100000,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $this->post(route('payables.payments.store', $payable), ['amount' => 150000]) // Over remaining
            ->assertSessionHasErrors('amount');
    }
}

