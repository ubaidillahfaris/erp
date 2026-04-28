<?php

use App\Jobs\CheckExpiryAlerts;
use App\Jobs\PostMonthlyDepreciation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckExpiryAlerts)->daily();
Schedule::job(new PostMonthlyDepreciation)->monthlyOn(1, '01:00');
