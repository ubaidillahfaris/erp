<?php

namespace Tests\Feature\Accounting;

use App\Models\Payable;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AgingReportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->user = User::factory()->create();
        $permission = Permission::create(['name' => 'view reports']);
        $this->user->givePermissionTo($permission);
    }

    public function test_payable_without_installment_uses_due_date()
    {
        $vendor = Vendor::factory()->create(['nama' => 'Test Vendor']);

        Payable::create([
            'type' => 'payable',
            'party_type' => 'vendor',
            'party_id' => $vendor->id,
            'principal_amount' => 100000,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'reference_type' => 'manual',
            'reference_id' => 1,
            'due_date' => Carbon::now()->subDays(10)->toDateString(), // 10 days overdue
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/accounting/aging');

        $response->assertStatus(200);
        $data = $response->inertiaPage()['props']['aging_lines'];

        $this->assertCount(1, $data);
        $this->assertEquals(10, $data[0]['days_overdue']);
        $this->assertEquals('days_30', $data[0]['bucket']);
    }

    public function test_payable_with_installment_uses_schedule_due_date()
    {
        $vendor = Vendor::factory()->create();

        $payable = Payable::create([
            'type' => 'payable',
            'party_type' => 'vendor',
            'party_id' => $vendor->id,
            'principal_amount' => 100000,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'reference_type' => 'manual',
            'reference_id' => 1,
            'installment_count' => 2,
            'interest_period' => 'daily',
            'due_date' => Carbon::now()->addDays(5)->toDateString(),
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        // Manual update schedules for predictable test
        $schedules = $payable->interestSchedules;
        $this->assertCount(2, $schedules);

        $schedules[0]->update(['due_date' => Carbon::now()->subDays(5)->toDateString()]); // 5 days overdue
        $schedules[1]->update(['due_date' => Carbon::now()->addDays(5)->toDateString()]); // 5 days current

        $response = $this->actingAs($this->user)
            ->get('/accounting/aging');

        $data = $response->inertiaPage()['props']['aging_lines'];
        $this->assertCount(2, $data);

        $overdue = collect($data)->where('days_overdue', 5)->first();
        $current = collect($data)->where('days_overdue', -5)->first();

        $this->assertNotNull($overdue);
        $this->assertNotNull($current);
        $this->assertEquals('days_30', $overdue['bucket']);
        $this->assertEquals('current', $current['bucket']);
    }

    public function test_current_bucket_for_future_due_date()
    {
        $vendor = Vendor::factory()->create();
        Payable::create([
            'type' => 'payable',
            'party_type' => 'vendor',
            'party_id' => $vendor->id,
            'principal_amount' => 100000,
            'total_amount' => 100000,
            'remaining_amount' => 100000,
            'reference_type' => 'manual',
            'reference_id' => 1,
            'due_date' => Carbon::now()->addDays(10)->toDateString(),
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get('/accounting/aging');
        $data = $response->inertiaPage()['props']['aging_lines'];

        $this->assertEquals('current', $data[0]['bucket']);
        $this->assertEquals(-10, $data[0]['days_overdue']);
    }

    public function test_over_90_bucket_for_old_overdue()
    {
        $vendor = Vendor::factory()->create();
        Payable::create([
            'type' => 'payable',
            'party_type' => 'vendor',
            'party_id' => $vendor->id,
            'principal_amount' => 100000,
            'total_amount' => 100000,
            'remaining_amount' => 100000,
            'reference_type' => 'manual',
            'reference_id' => 1,
            'due_date' => Carbon::now()->subDays(100)->toDateString(),
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get('/accounting/aging');
        $data = $response->inertiaPage()['props']['aging_lines'];

        $this->assertEquals('over_90', $data[0]['bucket']);
        $this->assertEquals(100, $data[0]['days_overdue']);
    }

    public function test_as_of_date_filter_skips_cache()
    {
        $spy = Cache::spy();

        // Call with a date that is NOT today should NOT use cache
        $this->actingAs($this->user)->get('/accounting/aging?as_of_date='.Carbon::now()->addDay()->toDateString());
        $spy->shouldNotHaveReceived('remember', ['aging_report', \Mockery::any(), \Mockery::any()]);

        // Call with today (default) should use cache
        $this->actingAs($this->user)->get('/accounting/aging');
        $spy->shouldHaveReceived('remember')->with('aging_report', 300, \Mockery::type('Closure'));
    }

    public function test_summary_totals_match_line_items()
    {
        $vendor = Vendor::factory()->create();
        Payable::create([
            'type' => 'payable',
            'party_type' => 'vendor',
            'party_id' => $vendor->id,
            'principal_amount' => 150000,
            'total_amount' => 150000,
            'remaining_amount' => 150000,
            'reference_type' => 'manual',
            'reference_id' => 1,
            'due_date' => Carbon::now()->subDays(10)->toDateString(),
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        Payable::create([
            'type' => 'receivable',
            'party_type' => 'vendor',
            'party_id' => $vendor->id,
            'principal_amount' => 50000,
            'total_amount' => 50000,
            'remaining_amount' => 50000,
            'reference_type' => 'manual',
            'reference_id' => 2,
            'due_date' => Carbon::now()->subDays(40)->toDateString(),
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get('/accounting/aging');
        $summary = $response->inertiaPage()['props']['summary'];

        $this->assertEquals(150000, $summary['payable']['days_30']);
        $this->assertEquals(150000, $summary['payable']['total']);
        $this->assertEquals(50000, $summary['receivable']['days_60']);
        $this->assertEquals(50000, $summary['receivable']['total']);
    }
}
