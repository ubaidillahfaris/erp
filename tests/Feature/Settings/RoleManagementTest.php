<?php

namespace Tests\Feature\Settings;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create superadmin role and user
        $role = Role::create(['name' => 'superadmin']);
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole($role);

        // Create some dummy menus and permissions
        Permission::create(['name' => 'view settings']);
        Permission::create(['name' => 'view users']);

        Menu::create([
            'name' => 'Settings',
            'permission_name' => 'view settings',
            'route_name' => 'settings.index',
            'order' => 1,
        ]);

        Menu::create([
            'name' => 'Users',
            'permission_name' => 'view users',
            'route_name' => 'users.index',
            'order' => 2,
        ]);
    }

    public function test_superadmin_can_view_roles(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('roles.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('settings/Roles')
            ->has('roles')
            ->has('menus')
        );
    }

    public function test_superadmin_can_create_role_with_menus(): void
    {
        $menus = Menu::all();

        $response = $this->actingAs($this->superAdmin)->post(route('roles.store'), [
            'name' => 'Manager',
            'menu_ids' => $menus->pluck('id')->toArray(),
        ]);

        $response->assertRedirect(route('roles.index'));

        $role = Role::findByName('Manager');
        $this->assertNotNull($role);

        // Verify permissions are synced from menus
        $this->assertTrue($role->hasPermissionTo('view settings'));
        $this->assertTrue($role->hasPermissionTo('view users'));

        // Verify menu mapping in database
        foreach ($menus as $menu) {
            $this->assertDatabaseHas('menu_role', [
                'role_id' => $role->id,
                'menu_id' => $menu->id,
            ]);
        }
    }

    public function test_superadmin_can_update_role_permissions(): void
    {
        $role = Role::create(['name' => 'Editor']);
        $menu1 = Menu::where('name', 'Settings')->first();
        $menu2 = Menu::where('name', 'Users')->first();

        // Map only one menu initially
        $role->menus()->attach($menu1->id);

        $response = $this->actingAs($this->superAdmin)->put(route('roles.update', $role->id), [
            'name' => 'Editor Updated',
            'menu_ids' => [$menu2->id], // Switch to other menu
        ]);

        $response->assertRedirect(route('roles.index'));

        $role->refresh();
        $this->assertEquals('Editor Updated', $role->name);

        // Verify permissions changed
        $this->assertFalse($role->hasPermissionTo('view settings'));
        $this->assertTrue($role->hasPermissionTo('view users'));
    }

    public function test_cannot_delete_protected_roles(): void
    {
        $cashierRole = Role::create(['name' => 'cashier']);

        // Try to delete superadmin
        $superAdminRole = Role::findByName('superadmin');
        $response = $this->actingAs($this->superAdmin)->delete(route('roles.destroy', $superAdminRole->id));
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Role sistem tidak dapat dihapus.');

        // Try to delete cashier
        $response = $this->actingAs($this->superAdmin)->delete(route('roles.destroy', $cashierRole->id));
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Role sistem tidak dapat dihapus.');
    }

    public function test_can_delete_custom_role(): void
    {
        $role = Role::create(['name' => 'Temporary']);

        $response = $this->actingAs($this->superAdmin)->delete(route('roles.destroy', $role->id));

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['name' => 'Temporary']);
    }
}
