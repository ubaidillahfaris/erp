<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'cashier']);
    }

    public function test_superadmin_can_access_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_cashier_cannot_access_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('cashier');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(403);
    }

    public function test_cashier_can_access_pos()
    {
        $user = User::factory()->create();
        $user->assignRole('cashier');

        $response = $this->actingAs($user)->get(route('pos.index'));

        $response->assertStatus(200);
    }

    public function test_cashier_cannot_access_produk()
    {
        $user = User::factory()->create();
        $user->assignRole('cashier');

        $response = $this->actingAs($user)->get(route('produk.index'));

        $response->assertStatus(403);
    }
}
