<?php

namespace App\Jobs;

use App\Models\StockBatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckExpiryAlerts implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = now()->startOfDay();
        $warningDate = now()->addDays(30)->startOfDay();

        // 1. Mark Expired
        StockBatch::where('expiry_date', '<', $today)
            ->where('status', '!=', 'expired')
            ->update(['status' => 'expired']);

        // 2. Mark Expiring Soon
        StockBatch::whereBetween('expiry_date', [$today, $warningDate])
            ->where('status', '!=', 'expiring_soon')
            ->update(['status' => 'expiring_soon']);

        // 3. Mark OK (if expiry was pushed back or just moved out of warning range)
        StockBatch::where('expiry_date', '>', $warningDate)
            ->where('status', '!=', 'ok')
            ->update(['status' => 'ok']);
    }
}
