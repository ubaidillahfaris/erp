<?php

namespace App\Observers;

use App\Models\Company;
use Database\Seeders\ChartOfAccountsSeeder;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        // Seed default chart of accounts for the new company
        (new ChartOfAccountsSeeder())->seedForCompany($company->id);
    }
}
