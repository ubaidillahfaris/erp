<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view dashboard',
            'manage users',
            'manage roles',
            'manage products',
            'manage vendors',
            'manage stock',
            'view reports',
            'make sales',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign existing permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin']);
        $superAdminRole->givePermissionTo(Permission::all());

        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);
        $cashierRole->givePermissionTo([
            'view dashboard',
            'make sales',
            'manage products', // Minimal access to see items
        ]);

        // Assign roles to existing users or create default ones
        $admin = User::firstOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole($superAdminRole);

        $cashier = User::firstOrCreate(
            ['email' => 'cashier@mail.com'],
            [
                'name' => 'Kasir Utama',
                'password' => bcrypt('password'),
            ]
        );
        $cashier->assignRole($cashierRole);
    }
}
