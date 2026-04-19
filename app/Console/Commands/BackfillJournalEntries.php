<?php

namespace App\Console\Commands;

use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\JournalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillJournalEntries extends Command
{
    protected $signature = 'accounting:backfill-journals
                            {--dry-run : Preview what would be created without saving}
                            {--force : Re-create entries even if they exist}';

    protected $description = 'Backfill JournalEntry records from existing finalized Purchases and completed Sales';

    public function __construct(protected JournalService $journalService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($isDryRun) {
            $this->warn('DRY RUN — no data will be saved.');
        }

        // Pre-check: Required accounts must exist
        $requiredCodes = ['1101', '1102', '1301', '1302', '2101', '4101', '5101', '6201'];
        $accounts = Account::whereIn('code', $requiredCodes)->pluck('id', 'code');
        $missingCodes = array_diff($requiredCodes, $accounts->keys()->toArray());

        if (!empty($missingCodes)) {
            $this->error('Missing required accounts: ' . implode(', ', $missingCodes));
            $this->error('Run: php artisan db:seed --class=ChartOfAccountsSeeder');

            return self::FAILURE;
        }

        $this->backfillPurchases($accounts, $isDryRun, $force);
        $this->backfillSales($accounts, $isDryRun, $force);
        $this->backfillExpenses($accounts, $isDryRun, $force);

        $this->newLine();
        $this->info('Backfill complete.');

        return self::SUCCESS;
    }

    protected function backfillPurchases(object $accounts, bool $isDryRun, bool $force): void
    {
        $this->info('--- [1/2] Backfilling Purchases ---');

        $purchases = Purchase::where('status', 'finalized')
            ->where('transaction_type', 'purchase')
            ->get();

        $this->line("Found {$purchases->count()} finalized purchases.");

        $created = 0;
        $skipped = 0;

        foreach ($purchases as $purchase) {
            $refNumber = sprintf('PUR-%s-%d', $purchase->tanggal->format('Ymd'), $purchase->id);

            // Skip if already journaled (unless force)
            if (!$force && JournalEntry::where('ref_number', $refNumber)->exists()) {
                $skipped++;
                continue;
            }

            $amount = (float) ($purchase->total_biaya ?? 0);

            if ($amount <= 0) {
                $this->warn("  Skipping Purchase #{$purchase->id}: amount is zero.");
                continue;
            }

            $amountCents = (int) round($amount * 100);
            $vendorName = $purchase->vendor?->nama ?? 'Unknown Vendor';
            $paymentMethod = $purchase->payment_method ?? 'cash';

            // Credit account depends on payment_method
            $creditAccCode = $paymentMethod === 'credit' ? '2101' : '1101';
            $description = match ($paymentMethod) {
                'credit'   => "Pembelian kredit dari vendor: {$vendorName}",
                'transfer' => "Pembelian via transfer dari vendor: {$vendorName}",
                default    => "Pembelian tunai dari vendor: {$vendorName}",
            };

            $this->line("  Creating: {$refNumber} [{$paymentMethod}] — Rp " . number_format($amount, 0, ',', '.'));

            if (!$isDryRun) {
                // Delete old entry if force
                if ($force) {
                    \App\Models\JournalEntry::where('ref_number', $refNumber)->delete();
                }

                $this->journalService->record(new JournalEntryData(
                    items: [
                        new JournalItemData($accounts['1301'], $amountCents, 'debit'),
                        new JournalItemData($accounts[$creditAccCode], $amountCents, 'credit'),
                    ],
                    tanggal: $purchase->tanggal,
                    ref_number: $refNumber,
                    description: $description,
                    journalable: $purchase
                ));
            }

            $created++;
        }

        $this->line("  Created: $created  |  Skipped (already exist): $skipped");
    }

    protected function backfillSales(object $accounts, bool $isDryRun, bool $force): void
    {
        $this->info('--- [2/2] Backfilling Sales ---');

        $sales = Sale::where('status', 'completed')->get();

        $this->line("Found {$sales->count()} completed sales.");

        $created = 0;
        $skipped = 0;

        foreach ($sales as $sale) {
            $refBase = sprintf('SALE-%s-%d', $sale->tanggal->format('Ymd'), $sale->id);
            $refRev = "{$refBase}-REV";
            $refCogs = "{$refBase}-COGS";

            // Skip if already journaled (unless force)
            if (!$force && JournalEntry::where('ref_number', $refRev)->exists()) {
                $skipped++;
                continue;
            }

            $revenueAmount = (float) ($sale->total_amount ?? 0);

            if ($revenueAmount <= 0) {
                $this->warn("  Skipping Sale #{$sale->id}: revenue amount is zero.");
                continue;
            }

            $revenueAmountCents = (int) round($revenueAmount * 100);

            // Recalculate COGS from items if not cached
            $cogsAmount = (int) ($sale->cogs_amount ?? 0);
            if ($cogsAmount <= 0) {
                $sale->loadMissing('items');
                $cogsCalc = $sale->items->sum(fn ($item) => $item->cost * $item->qty);
                $cogsAmount = (int) round($cogsCalc * 100);
            }

            $invoiceNumber = $sale->invoice_number ?? "SALE-{$sale->id}";
            $this->line("  Creating Revenue: {$refRev} — Rp " . number_format($revenueAmount, 0, ',', '.'));

            if (!$isDryRun) {
                // Delete old entries if force
                if ($force) {
                    JournalEntry::whereIn('ref_number', [$refRev, $refCogs])->delete();
                }

                DB::transaction(function () use ($sale, $accounts, $revenueAmountCents, $cogsAmount, $refRev, $refCogs, $invoiceNumber) {
                    // 1. Revenue Entry: Dr Piutang Usaha / Cr Penjualan
                    $this->journalService->record(new JournalEntryData(
                        items: [
                            new JournalItemData($accounts['1102'], $revenueAmountCents, 'debit'),
                            new JournalItemData($accounts['4101'], $revenueAmountCents, 'credit'),
                        ],
                        tanggal: $sale->tanggal,
                        ref_number: $refRev,
                        description: "Revenue Penjualan INV-{$invoiceNumber}",
                        journalable: $sale
                    ));

                    // 2. COGS Entry (only if COGS can be determined)
                    if ($cogsAmount > 0) {
                        $this->line("  Creating COGS:    {$refCogs} — Rp " . number_format($cogsAmount / 100, 0, ',', '.'));
                        $this->journalService->record(new JournalEntryData(
                            items: [
                                new JournalItemData($accounts['5101'], $cogsAmount, 'debit'),
                                new JournalItemData($accounts['1302'], $cogsAmount, 'credit'),
                            ],
                            tanggal: $sale->tanggal,
                            ref_number: $refCogs,
                            description: "COGS Penjualan INV-{$invoiceNumber}",
                            journalable: $sale
                        ));
                    }
                });
            }

            $created++;
        }

        $this->line("  Created: $created  |  Skipped (already exist): $skipped");
    }

    protected function backfillExpenses(object $accounts, bool $isDryRun, bool $force): void
    {
        $this->info('--- [3/3] Backfilling Expenses ---');

        $expenses = \App\Models\Pengeluaran::all();

        $this->line("Found {$expenses->count()} operational expenses.");

        $created = 0;
        $skipped = 0;

        foreach ($expenses as $expense) {
            $refNumber = sprintf('EXP-%s-%04d', $expense->tanggal->format('Ymd'), $expense->id);

            // Skip if already journaled (unless force)
            if (!$force && JournalEntry::where('ref_number', $refNumber)->exists()) {
                $skipped++;
                continue;
            }

            $amount = (float) $expense->nominal;
            if ($amount <= 0) continue;

            $amountCents = (int) round($amount * 100);

            // Determine Debit Account (Expense)
            $expenseAccId = $expense->account_id ?? $accounts['6201'];
            
            $this->line("  Creating: {$refNumber} — Rp " . number_format($amount, 0, ',', '.'));

            if (!$isDryRun) {
                if ($force) {
                    JournalEntry::where('ref_number', $refNumber)->delete();
                }

                $this->journalService->record(new JournalEntryData(
                    items: [
                        new JournalItemData($expenseAccId, $amountCents, 'debit'),
                        new JournalItemData($accounts['1101'], $amountCents, 'credit'),
                    ],
                    tanggal: $expense->tanggal,
                    ref_number: $refNumber,
                    description: "Backfill Pengeluaran: {$expense->nama_pengeluaran}",
                    journalable: $expense
                ));
            }

            $created++;
        }

        $this->line("  Created: $created  |  Skipped (already exist): $skipped");
    }
}
