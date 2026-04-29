<?php

namespace App\Console\Commands;

use App\Models\Unit;
use Database\Seeders\AccountingMenuSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\CustomerStatusSeeder;
use Database\Seeders\CustomerTypeSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\MaterialAndPlasticSeeder;
use Database\Seeders\MenuRoleSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\NasabahStatusSeeder;
use Database\Seeders\NewFeaturesMenuSeeder;
use Database\Seeders\PayableSeeder;
use Database\Seeders\PayablesMenuSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalesMenuSeeder;
use Database\Seeders\TransaksiModuleSeeder;
use Database\Seeders\UnitConversionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Console\Command;

class AppSetupCommand extends Command
{
    protected $signature = 'app:setup {--demo : Install with sample data} {--laundry : Install laundry specific defaults}';

    protected $description = 'Initialize application with core or demo data';

    public function handle()
    {
        $isDemo = $this->option('demo');
        $isLaundry = $this->option('laundry');

        $this->info($isDemo ? '🚀 Initializing App in DEMO mode...' : '🛡️ Initializing App in PRODUCTION mode...');

        // 1. Core Seeders (Always Run)
        $coreSeeders = [
            ModuleSeeder::class,
            CustomerTypeSeeder::class,
            CustomerStatusSeeder::class,
            NasabahStatusSeeder::class,
            RoleAndPermissionSeeder::class,
            MenuSeeder::class,
            AccountingMenuSeeder::class,
            SalesMenuSeeder::class,
            PayablesMenuSeeder::class,
            NewFeaturesMenuSeeder::class,
            MenuRoleSeeder::class,
            ChartOfAccountsSeeder::class,
            TransaksiModuleSeeder::class,
        ];

        foreach ($coreSeeders as $seeder) {
            $this->info("Seeding: {$seeder}");
            $this->call('db:seed', ['--class' => $seeder, '--force' => true]);
        }

        // 2. Laundry Specific Defaults
        if ($isLaundry) {
            $this->info('🧺 Setting up Laundry defaults...');
            // We can create a quick LaundryInitSeeder or just do it here
            $this->setupLaundryDefaults();
        }

        // 3. Demo/Sample Seeders
        if ($isDemo) {
            $demoSeeders = [
                MaterialAndPlasticSeeder::class,
                UserSeeder::class,
                PayableSeeder::class,
                EmployeeSeeder::class,
                UnitConversionSeeder::class,
            ];

            foreach ($demoSeeders as $seeder) {
                $this->info("Seeding Demo Data: {$seeder}");
                $this->call('db:seed', ['--class' => $seeder, '--force' => true]);
            }
        }

        $this->success('✅ App Setup Completed successfully!');
    }

    protected function setupLaundryDefaults()
    {
        // Add Laundry Units if they don't exist
        Unit::firstOrCreate(['name' => 'KG', 'code' => 'kg']);
        Unit::firstOrCreate(['name' => 'PCS', 'code' => 'pcs']);
        Unit::firstOrCreate(['name' => 'METER', 'code' => 'm']);

        $this->info(' - Units KG, PCS, METER created.');
    }

    protected function success($message)
    {
        $this->output->writeln("<info>{$message}</info>");
    }
}
