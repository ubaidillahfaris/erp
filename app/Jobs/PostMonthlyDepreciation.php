<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\DepreciationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PostMonthlyDepreciation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(DepreciationService $service): void
    {
        // Post depreciation for the PREVIOUS month (since we run on the 1st)
        $date = now()->subMonth();
        $month = (int) $date->format('m');
        $year = (int) $date->format('Y');

        try {
            $count = $service->postPeriod($month, $year);
            Log::info("Auto-posted depreciation for {$month}/{$year}. Total assets: {$count}");
        } catch (\Exception $e) {
            Log::error('Failed to auto-post depreciation: '.$e->getMessage());
        }
    }
}
