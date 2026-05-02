<?php

namespace Database\Seeders;

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
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            ServiceMenuSeeder::class,
            MenuSeeder::class,
            MenuRoleSeeder::class,
            AccountingMenuSeeder::class,
            SalesMenuSeeder::class,
            TransaksiModuleSeeder::class,
            PayablesMenuSeeder::class,
            NewFeaturesMenuSeeder::class,
            CustomerTypeSeeder::class,
            CustomerStatusSeeder::class,
            ChartOfAccountsSeeder::class,
        ]);
    }
}
