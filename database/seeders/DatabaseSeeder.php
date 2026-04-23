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
        // User::factory(10)->create();

        $this->call([
            ModuleSeeder::class,
            CustomerTypeSeeder::class,
            CustomerStatusSeeder::class,
            NasabahStatusSeeder::class,
            RoleAndPermissionSeeder::class,
            MenuSeeder::class,
            MenuRoleSeeder::class,
            SatuanConversionSeeder::class,
            // BakeryStoreSeeder::class,
            ChartOfAccountsSeeder::class,
            AccountingMenuSeeder::class,
            MaterialAndPlasticSeeder::class,
            UserSeeder::class,
            SalesMenuSeeder::class,
            PayableSeeder::class,
            TransaksiModuleSeeder::class,
            PayablesMenuSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
