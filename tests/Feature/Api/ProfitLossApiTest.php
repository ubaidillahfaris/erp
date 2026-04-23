<?php

namespace Tests\Feature\Api;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfitLossApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'superadmin']);
    }

    public function test_superadmin_can_access_profit_loss_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        Sanctum::actingAs($user);

        // Seed income
        Journal::factory()->create([
            'tanggal' => now()->toDateString(),
            'type' => 'debit',
            'amount' => 5000,
            'category' => 'sales',
        ]);

        // Seed expense
        Journal::factory()->create([
            'tanggal' => now()->toDateString(),
            'type' => 'kredit',
            'amount' => 2000,
            'category' => 'expense',
        ]);

        $response = $this->getJson('/api/v1/profit-loss');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_income' => 5000,
                        'total_expense' => 2000,
                        'net_profit' => 3000,
                    ],
                ],
            ]);
    }

    public function test_profit_loss_filtering_by_date(): void
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        Sanctum::actingAs($user);

        // Entry in current month
        Journal::factory()->create([
            'tanggal' => now()->toDateString(),
            'type' => 'debit',
            'amount' => 1000,
        ]);

        // Entry in last month
        Journal::factory()->create([
            'tanggal' => now()->subMonth()->toDateString(),
            'type' => 'debit',
            'amount' => 500,
        ]);

        // Test default (current month)
        $response = $this->getJson('/api/v1/profit-loss');
        $this->assertEquals(1000, $response->json('data.summary.total_income'));

        // Test with date range (all time)
        $startDate = now()->subMonths(2)->toDateString();
        $endDate = now()->toDateString();
        $response = $this->getJson("/api/v1/profit-loss?start_date={$startDate}&end_date={$endDate}");
        $this->assertEquals(1500, $response->json('data.summary.total_income'));
    }
}
