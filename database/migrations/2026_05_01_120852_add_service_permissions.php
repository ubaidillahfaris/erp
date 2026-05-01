<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create permissions if they don't exist
        $permissions = [
            'manage services',
            'make sales', // Should exist, but ensure just in case
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // 2. Assign to Superadmin & Owner
        $roles = Role::whereIn('name', ['superadmin', 'owner'])->get();
        foreach ($roles as $role) {
            $role->givePermissionTo('manage services');
            $role->givePermissionTo('make sales');
        }

        // Clear permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep permissions for safety, or delete if strictly needed
    }
};
