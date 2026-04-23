<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create employees for existing users
        $users = User::all();
        
        foreach ($users as $user) {
            // Skip if employee already exists for this user
            if ($user->employee) {
                continue;
            }

            Employee::factory()->create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'position' => $user->hasRole('admin') ? 'Manager' : 'Staff',
                'department' => $user->hasRole('admin') ? 'Administrasi' : 'Operasional',
            ]);
        }

        // 2. Create 30 employees without users
        Employee::factory()->count(30)->create();
    }
}
