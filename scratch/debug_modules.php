<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

try {
    $modules = DB::table('modules')->get();
    echo json_encode($modules, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
