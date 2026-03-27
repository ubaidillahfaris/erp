<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Journal;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;

class JournalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'cashier']);
    }

    public function test_superadmin_can_access_journal_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        Sanctum::actingAs($user);

        Journal::factory()->create([
            'tanggal' => now()->toDateString(),
            'type' => 'debit',
            'amount' => 1000,
            'category' => 'sales',
        ]);

        $response = $this->getJson('/api/v1/journal');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'tanggal',
                        'type',
                        'amount',
                        'category',
                        'balance',
                    ]
                ],
                'links',
                'meta',
            ]);
        
        $this->assertEquals(1000, $response->json('data.0.balance'));
    }

    public function test_journal_running_balance_calculation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        Sanctum::actingAs($user);

        // Entry 1 (Oldest)
        Journal::factory()->create(['tanggal' => '2023-01-01', 'type' => 'debit', 'amount' => 1000]);
        // Entry 2
        Journal::factory()->create(['tanggal' => '2023-01-02', 'type' => 'kredit', 'amount' => 300]);
        // Entry 3 (Newest)
        Journal::factory()->create(['tanggal' => '2023-01-03', 'type' => 'debit', 'amount' => 500]);

        $response = $this->getJson('/api/v1/journal?per_page=2&page=1');

        $response->assertStatus(200);
        
        // Items on page 1 (Newest first): Entry 3, Entry 2
        // Total balance = 1000 - 300 + 500 = 1200
        // Entry 3 balance = 1200
        // Entry 2 balance = 1200 - 500 = 700
        
        $this->assertEquals(1200, $response->json('data.0.balance'));
        $this->assertEquals(700, $response->json('data.1.balance'));

        // Page 2: Entry 1
        $response = $this->getJson('/api/v1/journal?per_page=2&page=2');
        $response->assertStatus(200);
        
        // Entry 1 balance = 700 - (-300) = 1000
        $this->assertEquals(1000, $response->json('data.0.balance'));
    }

    public function test_non_superadmin_cannot_access_journal_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('cashier');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/journal');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_journal_api(): void
    {
        $response = $this->getJson('/api/v1/journal');

        $response->assertStatus(401);
    }
}
