<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup initial roles
        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'cashier']);
    }

    public function test_superadmin_can_access_user_management(): void
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertStatus(200);
    }

    public function test_cashier_cannot_access_user_management(): void
    {
        $user = User::factory()->create();
        $user->assignRole('cashier');

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertStatus(403);
    }

    public function test_superadmin_can_create_user_with_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'cashier',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);

        $newUser = User::where('email', 'new@example.com')->first();
        $this->assertTrue($newUser->hasRole('cashier'));
    }

    public function test_superadmin_can_update_user_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $user = User::factory()->create();
        $user->assignRole('cashier');

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => 'superadmin',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertTrue($user->fresh()->hasRole('superadmin'));
        $this->assertFalse($user->fresh()->hasRole('cashier'));
    }

    public function test_superadmin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
