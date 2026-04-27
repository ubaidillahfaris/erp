<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        Permission::create(['name' => 'manage stock']);
        $this->admin->givePermissionTo('manage stock');
    }

    public function test_can_list_warehouses()
    {
        Warehouse::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/warehouses');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Warehouses/Index')
            ->has('warehouses', 4) // 3 + 1 default from migration/seeder (if run)
        );
    }

    public function test_can_create_warehouse()
    {
        $data = [
            'name' => 'Gudang Baru',
            'code' => 'GBR',
            'address' => 'Jl. Baru No. 1',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->from('/warehouses')
            ->post('/warehouses', $data);

        $response->assertRedirect('/warehouses');
        $this->assertDatabaseHas('warehouses', ['code' => 'GBR']);
    }

    public function test_cannot_delete_default_warehouse()
    {
        $default = Warehouse::where('is_default', true)->first();
        if (! $default) {
            $default = Warehouse::factory()->create(['is_default' => true]);
        }

        $response = $this->actingAs($this->admin)->delete("/warehouses/{$default->id}");

        $response->assertSessionHas('error', 'Gudang utama tidak dapat dihapus.');
        $this->assertDatabaseHas('warehouses', ['id' => $default->id]);
    }
}
