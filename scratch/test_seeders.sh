#!/bin/bash
set -e
echo "Running ModuleSeeder..."
php artisan db:seed --class=ModuleSeeder
echo "Running ServiceMenuSeeder..."
php artisan db:seed --class=ServiceMenuSeeder
echo "Running RoleAndPermissionSeeder..."
php artisan db:seed --class=RoleAndPermissionSeeder
echo "Running MenuSeeder..."
php artisan db:seed --class=MenuSeeder
echo "Running MenuRoleSeeder..."
php artisan db:seed --class=MenuRoleSeeder
echo "Running AccountingMenuSeeder..."
php artisan db:seed --class=AccountingMenuSeeder
echo "Running SalesMenuSeeder..."
php artisan db:seed --class=SalesMenuSeeder
echo "Running TransaksiModuleSeeder..."
php artisan db:seed --class=TransaksiModuleSeeder
echo "Running PayablesMenuSeeder..."
php artisan db:seed --class=PayablesMenuSeeder
echo "Running NewFeaturesMenuSeeder..."
php artisan db:seed --class=NewFeaturesMenuSeeder
