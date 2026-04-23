<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo "Query 1 Results:\n";
$q1 = DB::select('SELECT id, name, path, is_active FROM menus WHERE module_id = 5 ORDER BY order_priority');
foreach ($q1 as $row) {
    echo sprintf("- ID: %d | Name: %-20s | Path: %-25s | Active: %s\n",
        $row->id, $row->name, $row->path, $row->is_active ? 'YES' : 'NO');
}

echo "\nQuery 2 Results:\n";
$q2 = DB::select('SELECT r.name as role, m.name as menu FROM menu_role mr JOIN roles r ON r.id = mr.role_id JOIN menus m ON m.id = mr.menu_id WHERE m.module_id = 5');
foreach ($q2 as $row) {
    echo sprintf("- Role: %-15s | Menu: %s\n", $row->role, $row->menu);
}
