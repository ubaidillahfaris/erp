<?php

namespace Tests\Feature;

use App\Jobs\LockPeriodJob;
use App\Models\PeriodLock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PeriodLockTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        Permission::create(['name' => 'make sales']);
        $this->user->givePermissionTo('make sales');
    }

    /** @test */
    public function test_it_blocks_request_if_period_is_locked()
    {
        // 1. Lock April 2024
        PeriodLock::create([
            'period_month' => 4,
            'period_year' => 2024,
            'is_locked' => true,
        ]);

        // 2. Attempt to POST to POS with date in April 2024
        $response = $this->actingAs($this->user)
            ->post(route('pos.store'), [
                'tanggal' => '2024-04-15',
                // other POS data...
            ]);

        $response->assertStatus(403);
        $response->assertSee('Periode Akuntansi April 2024 sudah dikunci');
    }

    /** @test */
    public function test_it_allows_request_if_period_is_not_locked()
    {
        // 1. Lock March 2024
        PeriodLock::create([
            'period_month' => 3,
            'period_year' => 2024,
            'is_locked' => true,
        ]);

        // 2. Attempt to POST to POS with date in April 2024 (unlocked)
        // Note: pos.store usually requires more data, but middleware runs first.
        // If it passes middleware, it might fail validation (422) or something else, but not 403 from period lock.
        $response = $this->actingAs($this->user)
            ->post(route('pos.store'), [
                'tanggal' => '2024-04-15',
            ]);

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function test_it_handles_now_if_no_date_provided()
    {
        $now = now();

        // Lock current month
        PeriodLock::create([
            'period_month' => $now->month,
            'period_year' => $now->year,
            'is_locked' => true,
        ]);

        // Attempt POST without date
        $response = $this->actingAs($this->user)
            ->post(route('pos.store'), []);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_lock_period_job_locks_previous_month()
    {
        $lastMonth = now()->subMonth();

        // Ensure no lock exists yet
        $this->assertDatabaseMissing('period_locks', [
            'period_month' => $lastMonth->month,
            'period_year' => $lastMonth->year,
            'is_locked' => true,
        ]);

        (new LockPeriodJob)->handle();

        $this->assertDatabaseHas('period_locks', [
            'period_month' => $lastMonth->month,
            'period_year' => $lastMonth->year,
            'is_locked' => true,
        ]);
    }
}
