<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Menu;
use App\Models\Module;
use Illuminate\Contracts\Console\Kernel;

$menus = Menu::whereIn('route_name', ['accounting.periods.index', 'system.audit-log.index'])->get();

echo 'Menus found: '.$menus->count()."\n";
foreach ($menus as $menu) {
    echo "ID: {$menu->id}, Name: {$menu->name}, Route: {$menu->route_name}, Module ID: ".($menu->module_id ?? 'NULL').', Active: '.($menu->is_active ? 'Yes' : 'No')."\n";
}

$modules = Module::all();
echo "\nModules found: ".$modules->count()."\n";
foreach ($modules as $module) {
    echo "ID: {$module->id}, Name: {$module->name}, Slug: {$module->slug}\n";
}
