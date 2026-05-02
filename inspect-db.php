<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$indexes = DB::select("
    SELECT indexname, indexdef
    FROM pg_indexes
    WHERE tablename = 'accounts'
");

print_r($indexes);
