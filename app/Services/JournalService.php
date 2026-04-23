<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\JournalEntryData;
use App\Exceptions\BalanceMismatchException;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalService
{
    /**
     * Record a double-entry journal transaction.
     *
     * @param  JournalEntryData  $data  DTO containing journal items, description, date, and reference
     *
     * @throws BalanceMismatchException if SUM(debit) !== SUM(credit)
     *
     * IMPORTANT: All monetary amounts in JournalItemData must be in
     * BigInt cents (integer). Example: Rp 1.000 = 100000.
     * Never pass float or Rupiah values directly.
     */
    public function record(JournalEntryData $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            $totalDebit = 0;
            $totalCredit = 0;
            $processedItems = [];

            // Logic: End-to-end values are already in cents.
            foreach ($data->items as $item) {
                $cents = $item->amount;

                if ($item->type === 'debit') {
                    $totalDebit += $cents;
                    $processedItems[] = [
                        'account_id' => $item->account_id,
                        'debit' => $cents,
                        'credit' => 0,
                    ];
                } else {
                    $totalCredit += $cents;
                    $processedItems[] = [
                        'account_id' => $item->account_id,
                        'debit' => 0,
                        'credit' => $cents,
                    ];
                }
            }

            // Hard Constraint: Validation
            if ($totalDebit !== $totalCredit) {
                throw new BalanceMismatchException($totalDebit - $totalCredit);
            }

            // Generate unique ref if not provided
            $refNumber = $data->ref_number ?? 'JN-'.now()->format('ymd').'-'.strtoupper(Str::random(6));

            /** @var JournalEntry $entry */
            $entry = JournalEntry::create([
                'ref_number' => $refNumber,
                'tanggal' => $data->tanggal ?? now(),
                'description' => $data->description,
                'journalable_id' => $data->journalable?->id,
                'journalable_type' => $data->journalable ? get_class($data->journalable) : null,
                'created_by' => $data->created_by ?? auth()->id(),
            ]);

            foreach ($processedItems as $item) {
                $entry->items()->create($item);
            }

            Cache::forget('trial_balance_current');

            return $entry;
        });
    }
}
