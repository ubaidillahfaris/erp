<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 0. Universal / Fallback Super Admin
        $admin = User::firstOrCreate([
            'email' => 'admin@gmail.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('superadmin');

        // 1. Owner / Super Admin
        $owner = User::firstOrCreate([
            'email' => 'owner@warung.com',
        ], [
            'name' => 'Owner Warung',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('superadmin');

        // 2. Production Manager (Manufaktur Plastik)
        $produksi = User::firstOrCreate([
            'email' => 'produksi@warung.com',
        ], [
            'name' => 'Manager Produksi Plastik',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $produksi->assignRole('superadmin'); // Production needs high access for BOM/Stock

        // 3. Store / Sales Manager (Toko Bangunan)
        $sales = User::firstOrCreate([
            'email' => 'sales@warung.com',
        ], [
            'name' => 'Store Manager Bangunan',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $sales->assignRole('superadmin');

        // 4. Cashier
        $kasir = User::firstOrCreate([
            'email' => 'kasir@warung.com',
        ], [
            'name' => 'Kasir Toko',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $kasir->assignRole('cashier');
    }
}
