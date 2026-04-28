<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Models\DepreciationSchedule;
use App\Models\FixedAsset;
use App\Models\PeriodLock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DepreciationService
{
    public function __construct(
        private JournalService $journalService
    ) {}

    /**
     * Generate the full depreciation schedule for an asset.
     */
    public function generateSchedule(FixedAsset $asset): void
    {
        $cost = $asset->acquisition_cost;
        $salvage = $asset->salvage_value;
        $life = $asset->useful_life_months;

        if ($life <= 0) {
            return;
        }

        $totalDepreciable = $cost - $salvage;
        $monthlyAmount = (int) floor($totalDepreciable / $life);

        $currentDate = Carbon::parse($asset->acquisition_date)->startOfMonth();
        $currentBookValue = $cost;

        for ($i = 1; $i <= $life; $i++) {
            $amount = $monthlyAmount;

            // Adjust for rounding in the last month
            if ($i === $life) {
                $amount = $currentBookValue - $salvage;
            }

            $currentBookValue -= $amount;

            // Move to next month for the schedule record
            // Usually depreciation starts the month after acquisition or the same month.
            // Requirement says "Generate full schedule from acquisition_date".
            // We'll record it for the month of acquisition + $i.
            $scheduleDate = $currentDate->copy()->addMonths($i);

            $asset->schedules()->create([
                'period_month' => $scheduleDate->month,
                'period_year' => $scheduleDate->year,
                'depreciation_amount' => $amount,
                'book_value_after' => $currentBookValue,
                'status' => 'scheduled',
            ]);
        }
    }

    /**
     * Post all scheduled depreciations for a specific month and year.
     */
    public function postPeriod(int $month, int $year): int
    {
        // Check for Period Lock
        $date = Carbon::createFromDate($year, $month, 1);
        if (PeriodLock::isLocked($date)) {
            throw new \RuntimeException("Period {$month}/{$year} is locked.");
        }

        return DB::transaction(function () use ($month, $year) {
            $schedules = DepreciationSchedule::where('period_month', $month)
                ->where('period_year', $year)
                ->where('status', 'scheduled')
                ->whereHas('asset', function ($query) {
                    $query->where('status', 'active');
                })
                ->with('asset')
                ->get();

            $count = 0;
            foreach ($schedules as $schedule) {
                $asset = $schedule->asset;

                $journalData = new JournalEntryData(
                    items: [
                        new JournalItemData(
                            account_id: $asset->expense_account_id,
                            amount: (int) $schedule->depreciation_amount,
                            type: 'debit'
                        ),
                        new JournalItemData(
                            account_id: $asset->depreciation_account_id,
                            amount: (int) $schedule->depreciation_amount,
                            type: 'credit'
                        ),
                    ],
                    date: Carbon::createFromDate($year, $month, 1)->endOfMonth(),
                    description: "Depreciation for {$asset->asset_code} - {$asset->name} ({$month}/{$year})",
                    journalable: $schedule
                );

                $entry = $this->journalService->record($journalData);

                $schedule->update([
                    'journal_entry_id' => $entry->id,
                    'status' => 'posted',
                ]);

                $asset->update([
                    'current_book_value' => $schedule->book_value_after,
                ]);

                // If book value reaches salvage value, mark as fully depreciated
                if ($asset->current_book_value <= $asset->salvage_value) {
                    $asset->update(['status' => 'fully_depreciated']);
                }

                $count++;
            }

            return $count;
        });
    }
}
