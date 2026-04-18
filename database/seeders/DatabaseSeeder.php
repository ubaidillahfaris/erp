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
            MenuModuleSeeder::class,
            CustomerTypeSeeder::class,
            CustomerStatusSeeder::class,
            NasabahStatusSeeder::class,
            RoleAndPermissionSeeder::class,
            MenuSeeder::class,
            MenuRoleSeeder::class,
            SatuanConversionSeeder::class,
            // BakeryStoreSeeder::class,
            MaterialAndPlasticSeeder::class,
            UserSeeder::class,
            CustomerMenuSeeder::class,
            SalesMenuSeeder::class,
            TransaksiModuleSeeder::class,
        ]);
    }
}
