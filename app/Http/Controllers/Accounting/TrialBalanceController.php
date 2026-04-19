<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class TrialBalanceController extends Controller
{
    public function index(): Response
    {
        $accounts = Cache::remember('trial_balance_current', 300, function () {
            return Account::query()
                ->where('is_active', true)
                ->withSum('journalItems', 'debit')
                ->withSum('journalItems', 'credit')
                ->orderBy('code')
                ->get();
        });

        $grandTotalDebit = 0;
        $grandTotalCredit = 0;

        foreach ($accounts as $account) {
            $grandTotalDebit += ($account->journal_items_sum_debit ?? 0);
            $grandTotalCredit += ($account->journal_items_sum_credit ?? 0);
        }

        $isBalanced = $grandTotalDebit === $grandTotalCredit;

        return Inertia::render('accounting/TrialBalance', [
            'accounts' => $accounts,
            'totals' => [
                'debit' => $grandTotalDebit,
                'credit' => $grandTotalCredit,
            ],
            'is_balanced' => $isBalanced,
            'generated_at' => now()->toDateTimeString(),
        ]);
    }
}
