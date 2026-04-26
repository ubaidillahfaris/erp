<?php

namespace App\Jobs;

use App\Models\PeriodLock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LockPeriodJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lastMonth = now()->subMonth();

        PeriodLock::updateOrCreate(
            [
                'period_month' => $lastMonth->month,
                'period_year' => $lastMonth->year,
            ],
            [
                'is_locked' => true,
            ]
        );
    }
}
