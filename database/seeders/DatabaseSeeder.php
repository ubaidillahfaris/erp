<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ModuleSeeder::class,
            ServiceMenuSeeder::class,
            RoleAndPermissionSeeder::class,
            MenuSeeder::class,
            MenuRoleSeeder::class,
            AccountingMenuSeeder::class,
            SalesMenuSeeder::class,
            TransaksiModuleSeeder::class,
            PayablesMenuSeeder::class,
            NewFeaturesMenuSeeder::class,
        ]);
    }
}
