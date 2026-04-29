<?php

use App\Models\Module;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = User::with('roles', 'company')->orderBy('id', 'desc')->first();

echo "--- DEBUG TENANCY ---\n";
echo 'User: '.($user ? $user->email : 'No user found')."\n";
if ($user) {
    echo 'Roles: '.json_encode($user->getRoleNames())."\n";
    echo 'Company Business Type: '.($user->company ? $user->company->business_type : 'NONE')."\n";
}
echo 'DB Module Slugs: '.json_encode(Module::pluck('slug')->toArray())."\n";
echo 'Config Blueprint (laundry): '.json_encode(config('business_presets.laundry.modules'))."\n";
