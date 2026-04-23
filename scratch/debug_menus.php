<?php

use App\Models\User;
use App\Services\RoleService;
use Database\Seeders\MenuSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$seeder = new RoleAndPermissionSeeder;
$seeder->run();
$seeder = new MenuSeeder;
$seeder->run();

$user = User::factory()->create();
$user->assignRole('superadmin');

$menus = app(RoleService::class)->getAuthorizedMenus($user);
echo 'Total modules/groups: '.count($menus)."\n";
$flatCount = 0;
foreach ($menus as $module) {
    $c = count($module['menus'] ?? []);
    $flatCount += $c;
    echo 'Group: '.$module['name'].' - Count: '.$c."\n";
}
echo 'Total Menus: '.$flatCount."\n";
