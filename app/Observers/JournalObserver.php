<?php

namespace App\Observers;

use App\Models\FinancialSummary;
use App\Models\Journal;
use Carbon\CarbonInterface;

class JournalObserver
{
    /**
     * Handle the Journal "created" event.
     */
    public function created(Journal $journal): void
    {
        $this->updateBalances($journal->date, $journal->id);
        $this->updateSummary($journal->date);
    }

    public function updated(Journal $journal): void
    {
        $this->updateBalances($journal->date, $journal->id);
        $this->updateSummary($journal->date);

        if ($journal->wasChanged('date')) {
            $this->updateBalances($journal->getOriginal('date'));
            $this->updateSummary($journal->getOriginal('date'));
        }
    }

    public function deleted(Journal $journal): void
    {
        $this->updateBalances($journal->date, $journal->id);
        $this->updateSummary($journal->date);
    }

    /**
     * Update running balances from a certain point forward.
     */
    private function updateBalances($date, $startId = null): void
    {
        // Get all journals from the starting date onwards, ordered by date and ID
        $journals = Journal::where('date', '>=', $date)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate starting balance from the record just before the first one in our list
        $firstJournal = $journals->first();
        if (! $firstJournal) {
            return;
        }

        $previousJournal = Journal::where('date', '<', $firstJournal->date)
            ->orWhere(function ($query) use ($firstJournal) {
                $query->where('date', $firstJournal->date)
                    ->where('id', '<', $firstJournal->id);
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $currentBalance = $previousJournal ? (float) $previousJournal->balance : 0;

        foreach ($journals as $item) {
            $impact = ($item->type === 'debit' ? (float) $item->amount : -(float) $item->amount);
            $currentBalance += $impact;

            // Update without triggering observers again to avoid infinite loop
            Journal::where('id', $item->id)->update(['balance' => (int) round($currentBalance)]);
        }
    }

    /**
     * Recalculate summary for a specific date.
     */
    private function updateSummary($date): void
    {
        if (! $date) {
            return;
        }

        // Ensure date is in Y-m-d format
        $dateString = $date instanceof CarbonInterface ? $date->format('Y-m-d') : $date;

        $stats = Journal::where('date', $dateString)
            ->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as total_debit")
            ->selectRaw("SUM(CASE WHEN type = 'kredit' THEN amount ELSE 0 END) as total_kredit")
            ->first();

        $totalDebit = (float) ($stats->total_debit ?? 0);
        $totalKredit = (float) ($stats->total_kredit ?? 0);

        // Debit = Money In, Kredit = Money Out
        $balance = $totalDebit - $totalKredit;

        if ($totalDebit == 0 && $totalKredit == 0) {
            FinancialSummary::where('date', $dateString)->delete();
        } else {
            FinancialSummary::updateOrCreate(
                ['date' => $dateString],
                [
                    'total_debit' => $totalDebit,
                    'total_kredit' => $totalKredit,
                    'final_balance' => $balance,
                ]
            );
        }
    }
}
