<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Journal;

class JournalBackfillBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'journal:backfill-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill running balance for all existing journals';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting balance backfill...');

        $journals = \App\Models\Journal::orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $currentBalance = 0;
        $bar = $this->output->createProgressBar($journals->count());

        foreach ($journals as $journal) {
            $impact = ($journal->type === 'debit' ? (float) $journal->amount : -(float) $journal->amount);
            $currentBalance += $impact;
            
            // Update without triggering observers again to avoid infinite loop
            Journal::where('id', $journal->id)->update(['balance' => (int) round($currentBalance)]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill completed successfully.');
    }
}
