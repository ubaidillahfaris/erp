<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ModuleSeeder::class);
        $this->seed(MenuSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('superadmin');
    }

    public function test_can_view_employee_index()
    {
        Employee::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get(route('employees.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('employees/Index')
            ->has('employees.data', 5)
        );
    }

    public function test_can_create_employee()
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('profile.jpg');

        $data = [
            'name' => 'John Doe',
            'nik' => '1234567890123456',
            'email' => 'john@example.com',
            'position' => 'Staff',
            'department' => 'IT',
            'join_date' => now()->format('Y-m-d'),
            'employment_type' => 'Tetap',
            'status' => 'active',
            'basic_salary' => 5000000,
            'photo' => $photo,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('employees.store'), $data);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'name' => 'John Doe',
            'nik' => '1234567890123456',
        ]);

        $employee = Employee::where('nik', '1234567890123456')->first();
        $this->assertNotNull($employee->photo_path);
        Storage::disk('public')->assertExists($employee->photo_path);
    }

    public function test_can_update_employee()
    {
        $employee = Employee::factory()->create();

        $data = array_merge($employee->toArray(), [
            'name' => 'Updated Name',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('employees.update', $employee), $data);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_employee()
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('employees.destroy', $employee));

        $response->assertRedirect(route('employees.index'));
        $this->assertSoftDeleted($employee);
    }
}
